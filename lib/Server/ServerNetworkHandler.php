<?php


namespace Konfigurator\Network\Server;


use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\BindContext;
use Amp\Socket\ResourceSocket;
use Amp\Socket\Server;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Network\AbstractNetworkHandler;
use Konfigurator\Network\Client\ClientNetworkHandler;
use Konfigurator\Network\Client\ClientNetworkHandlerEvent;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerState;
use function Amp\asyncCall;
use function Amp\call;

class ServerNetworkHandler extends AbstractNetworkHandler implements ServerNetworkHandlerInterface
{
    /** @var Server|null */
    protected ?Server $serverHandler;

    /** @var ClientNetworkHandlerInterface[] */
    protected array $clientHandlers = [];

    /** @var Deferred|null */
    protected ?Deferred $_handleAcceptor;


    /**
     * ServerNetworkHandler constructor.
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(NetworkEventDispatcher $eventDispatcher)
    {
        parent::__construct($eventDispatcher);

        $this->serverHandler = null;
        $this->clientHandlers = [];

        $this->_handleAcceptor = new Deferred();

        $self = &$this;

        $this->getEventDispatcher()
            ->addListener(ClientNetworkHandlerEvent::CONNECTED(), function (ClientNetworkHandlerEvent $event) use (&$self) {
                $self->registerClient($event->getNetworkHandler());
            }, NetworkEventDispatcher::PRIORITY_MAX)
            ->addListener(ClientNetworkHandlerEvent::DISCONNECTED(), function (ClientNetworkHandlerEvent $event) use (&$self) {
                $self->removeClient($event->getNetworkHandler()->getAddress());
            }, NetworkEventDispatcher::PRIORITY_MAX)
        ;
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        parent::shutdown();

        $this->close();
    }

    /**
     * @param SocketAddress $address
     * @return ClientNetworkHandlerInterface|null
     */
    public function getClientHandler(SocketAddress $address): ?ClientNetworkHandlerInterface
    {
        return $this->clientHandlers[$address->toString()] ?? null;
    }

    /**
     * @param SocketAddress $address
     * @return Promise<void>|Failure<\Throwable>
     */
    public function listen(SocketAddress $address): Promise
    {
        if ($this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return new Success();
        }

        return call(static function (self &$self, SocketAddress $address) {

            try {

                $self->getLogger()->debug("Initializing server socket at tcp://{$address}");

                $self->serverHandler = $self->createServerHandler($address);

                $self->getLogger()->info("Server listen connections at tcp://{$address}!");

            } catch (\Throwable $e) {

                $self->getLogger()->info("Server socket initialize failure!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

            $self->setState(NetworkHandlerState::RUNNING());

            asyncCall(static function (self &$self) {

                try {

                    while ($self->getState()->equals(NetworkHandlerState::RUNNING())) {

                        if (empty($self->serverHandler) || $self->serverHandler->isClosed()) {
                            break;
                        }

                        /** @var ResourceSocket|null $socket */
                        $socket = yield $self->serverHandler->accept();
                        if (!$socket) {
                            continue;
                        }

                        $self->getLogger()->debug("Connection established with {$socket->getRemoteAddress()}");

                        $self->createClientNetworkHandler($socket);

                        if (!empty($self->_handleAcceptor)) {
                            $self->_handleAcceptor->resolve();
                        }

                    }

                } catch (\Throwable $e) {

                    $self->getLogger()->info("Server accept connection error!", [
                        'exception' => $e,
                    ]);

                }

                $self->close();

            }, $self);

        }, $this, $address);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (!$this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return;
        }

        $this->getLogger()->debug("Server socket closed!");

        $this->setState(NetworkHandlerState::STOPPED());

        foreach ($this->clientHandlers as $clientHandler) {
            $clientHandler->disconnect();
        }
        $this->clientHandlers = [];

        //yield new Delayed(0);

        if ($this->serverHandler) {
            if (!$this->serverHandler->isClosed()) {
                $this->serverHandler->close();
            }
            $this->serverHandler = null;
        }
    }

    /**
     * @return Promise<void>
     */
    protected function _handle(): Promise
    {
        return call(static function (self &$self) {

            if (!empty($self->_handleAcceptor)) {
                yield $self->_handleAcceptor->promise();
                $self->_handleAcceptor = null;
            } else {
                if (sizeof($self->clientHandlers) == 0) {
                    $self->_handleAcceptor = new Deferred();
                    return;
                }
            }

            if (empty($self->serverHandler) || $self->serverHandler->isClosed()) {
                $self->close();
                return;
            }

            foreach ($self->clientHandlers as $clientHandler) {
                yield $clientHandler->handle();
            }

        }, $this);
    }

    /**
     * @param SocketAddress $address
     * @return Server
     * @throws \Amp\Socket\SocketException
     */
    protected function createServerHandler(SocketAddress $address): Server
    {
        return Server::listen("tcp://{$address}", (new BindContext())
            ->withTcpNoDelay()
            //->withChunkSize(16384)
        );
    }

    /**
     * @param ResourceSocket $socket
     * @return ClientNetworkHandlerInterface
     */
    protected function createClientNetworkHandler(ResourceSocket $socket): ClientNetworkHandlerInterface
    {
        $instance = ClientNetworkHandler::fromServerConnection($socket, $this->getEventDispatcher());
        $instance->setLogger($this->getLogger());
        return $instance;
    }

    /**
     * @param ClientNetworkHandlerInterface $clientNetworkHandler
     * @return void
     */
    protected function registerClient(ClientNetworkHandlerInterface $clientNetworkHandler): void
    {
        $this->clientHandlers[$clientNetworkHandler->getAddress()->toString()] = $clientNetworkHandler;
    }

    /**
     * @param SocketAddress $client
     * @return void
     */
    protected function removeClient(SocketAddress $client): void
    {
        unset($this->clientHandlers[$client->toString()]);
    }
}