<?php


namespace Konfigurator\Network\Server;


use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\BindContext;
use Amp\Socket\ResourceSocket;
use Amp\Socket\Server;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Common\Enums\StateEnum;
use Konfigurator\Common\Exceptions\PendingShutdownError;
use Konfigurator\Network\AbstractNetworkManager;
use Konfigurator\Network\Server\NetworkManager\ClientEventEnum;
use Konfigurator\Network\Server\NetworkManager\ServerEventEnum;
use Konfigurator\Network\Server\NetworkManager\ServerStateEnum;
use function Amp\asyncCall;
use function Amp\call;

class ServerNetworkManager extends AbstractNetworkManager implements ServerNetworkManagerInterface
{
    /** @var Server|null */
    protected ?Server $serverHandler;

    /** @var ResourceSocket[] */
    protected array $acceptedSockets;


    /**
     * ServerNetworkManager constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->serverHandler = null;
        $this->acceptedSockets = [];
    }

    /**
     * @param SocketAddress $address
     * @return ResourceSocket|null
     */
    public function getClientSocket(SocketAddress $address): ?ResourceSocket
    {
        return $this->acceptedSockets[$address->toString()] ?? null;
    }

    /**
     * @return ServerStateEnum
     */
    public function getState(): StateEnum
    {
        return parent::getState();
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
        );
    }

    /**
     * @param SocketAddress $address
     * @return Promise<void>|Failure<\Throwable>
     */
    public function listen(SocketAddress $address): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        if ($this->getState()->equals(ServerStateEnum::LISTEN())) {
            return new Success();
        }

        return call(static function (self &$self, SocketAddress $address) {

            try {

                $self->getLogger()->debug("Initializing server socket at tcp://{$address}");

                $self->serverHandler = $self->createServerHandler($address);

                $self->setState(ServerStateEnum::LISTEN());

                $self->getLogger()->info("Server listen connections at tcp://{$address}!");

                $self->notifyEventAcceptor(ServerEventEnum::LISTEN());

                yield new Delayed(0);

            } catch (\Throwable $e) {

                $self->setState(ServerStateEnum::CLOSED());

                $self->getLogger()->info("Server socket initialize failure!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

        }, $this, $address);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (!$this->getState()->equals(ServerStateEnum::LISTEN())) {
            return;
        }

        asyncCall(static function (self &$self) {

            foreach ($self->acceptedSockets as $acceptedSocket) {
                $acceptedSocket->close();
            }
            $self->acceptedSockets = [];

            yield new Delayed(0);

            if ($self->serverHandler) {
                if (!$self->serverHandler->isClosed()) {
                    $self->serverHandler->close();
                }
                $self->serverHandler = null;
            }

            $self->getLogger()->debug("Server socket closed!");

            $self->setState(ServerStateEnum::CLOSED());

            $self->notifyEventAcceptor(ServerEventEnum::CLOSED());

            yield new Delayed(0);

        }, $this);
    }

    /**
     * @param ResourceSocket $socket
     * @return void
     */
    protected function registerClient(ResourceSocket $socket): void
    {
        $this->acceptedSockets[$socket->getRemoteAddress()->toString()] = $socket;

        asyncCall(static function (self &$self, ResourceSocket $socket) {

            $remoteAddr = $socket->getRemoteAddress();

            $self->getLogger()->debug("Client socket {$remoteAddr} registered!");

            $self->notifyEventAcceptor(ClientEventEnum::CONNECTED()->setRemoteAddress($remoteAddr));

            yield new Delayed(0);

            try {

                while (!$socket->isClosed()) {

                    $packet = yield $socket->read();

                    if (!$packet) {
                        yield new Delayed(0);
                        continue;
                    }

                    $self->getLogger()->debug("RECV: packet length " . strlen($packet));

                    $self->notifyEventAcceptor(ClientEventEnum::PACKET_RECEIVED()->setRemoteAddress($remoteAddr)->withEventData($packet));

                    yield new Delayed(0);

                }

            } catch (\Throwable $e) {

                $self->getLogger()->error("Client socket handler exception", [
                    'exception' => $e,
                ]);

                if (!$socket->isClosed()) {
                    $socket->close();
                }

            }

            unset($self->acceptedSockets[$remoteAddr->toString()]);

            $self->getLogger()->debug("Client socket {$remoteAddr} removed!");

            $self->notifyEventAcceptor(ClientEventEnum::DISCONNECTED()->setRemoteAddress($remoteAddr));

            yield new Delayed(0);

        }, $this, $socket);
    }

    /**
     * @return Promise<void>
     */
    public function handle(): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        return call(static function (self &$self) {

            while ($self->getState()->equals(ServerStateEnum::LISTEN())) {

                /** @var ResourceSocket $socket */
                $socket = yield $self->serverHandler->accept();
                if (!$socket) {
                    yield new Delayed(0);
                    continue;
                }

                $self->registerClient($socket);
                yield new Delayed(0);

            }

        }, $this);
    }

    /**
     * @param SocketAddress $remoteAddr
     * @param string|\Stringable $packet
     * @return Promise<void>
     */
    public function sendPacket(SocketAddress $remoteAddr, $packet): Promise
    {
        return call(static function (self &$self, SocketAddress $remoteAddr, $packet){

            $socket = $self->getClientSocket($remoteAddr);
            if (!$socket) {
                return new Failure(new \LogicException("Invalid client socket {$remoteAddr}!"));
            }

            $self->getLogger()->debug("SEND: packet length " . strlen($packet));

            yield $socket->write($packet);

            return new Success();

        }, $this, $remoteAddr, $packet);
    }

    /**
     * @param SocketAddress $remoteAddr
     * @return Promise<void>
     */
    public function disconnect(SocketAddress $remoteAddr): Promise
    {
        return call(static function (self &$self, SocketAddress $remoteAddr) {

            $socket = $self->getClientSocket($remoteAddr);
            if (!$socket) {
                return new Failure(new \LogicException("Invalid client addr {$remoteAddr}!"));
            }

            $socket->close();

            return new Success();

        }, $this, $remoteAddr);
    }
}