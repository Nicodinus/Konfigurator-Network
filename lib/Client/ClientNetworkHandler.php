<?php


namespace Konfigurator\Network\Client;


use Amp\CancelledException;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket;
use Amp\Socket\ConnectContext;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Network\AbstractNetworkHandler;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerState;
use function Amp\call;

class ClientNetworkHandler extends AbstractNetworkHandler implements ClientNetworkHandlerInterface
{
    /** @var ResourceSocket|null */
    protected ?ResourceSocket $clientHandler;

    /** @var SocketAddress|null */
    protected ?SocketAddress $address;


    /**
     * ClientNetworkHandler constructor.
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(NetworkEventDispatcher $eventDispatcher)
    {
        parent::__construct($eventDispatcher);

        $this->clientHandler = null;
        $this->address = null;
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        parent::shutdown();

        $this->disconnect();
    }

    /**
     * @param ResourceSocket $connection
     * @param NetworkEventDispatcher $eventDispatcher
     * @return static
     */
    public static function fromServerConnection(ResourceSocket $connection, NetworkEventDispatcher $eventDispatcher): self
    {
        $instance = new static($eventDispatcher);

        $instance->clientHandler = $connection;
        $instance->address = $connection->getRemoteAddress();

        $instance->setState(NetworkHandlerState::RUNNING());
        $instance->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::CONNECTED($instance));

        return $instance;
    }

    /**
     * @return SocketAddress|null
     */
    public function getAddress(): ?SocketAddress
    {
        return $this->address;
    }

    /**
     * @param SocketAddress $address
     * @param ConnectContext|null $connectContext
     * @return Promise<void>|Failure<ConnectException|CancelledException>
     */
    public function connect(SocketAddress $address, ?ConnectContext $connectContext = null): Promise
    {
        if ($this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return new Success();
        }

        return call(static function (self &$self, SocketAddress $address, ?ConnectContext $connectContext = null) {

            try {

                $self->getLogger()->debug("Connecting to tcp://{$address}...");

                $self->clientHandler = yield $self->createConnectionHandler($address, $connectContext);
                $self->address = $self->clientHandler->getLocalAddress();

                $self->getLogger()->info("Connection successful established with tcp://{$address}!");

                //yield new Delayed(0);

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::connect exception!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

            $self->setState(NetworkHandlerState::RUNNING());
            $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::CONNECTED($self));

        }, $this, $address, $connectContext);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        if (!$this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return;
        }

        $this->getLogger()->info("Connection closed!");

        $this->setState(NetworkHandlerState::STOPPED());

        if ($this->clientHandler) {
            if (!$this->clientHandler->isClosed()) {
                $this->clientHandler->close();
            }
            $this->clientHandler = null;
        }

        $this->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::DISCONNECTED($this));
    }

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>|Failure<ConnectException>
     */
    public function sendPacket($packet): Promise
    {
        return call(static function (self &$self, $packet) {

            if (!$self->getState()->equals(NetworkHandlerState::RUNNING())) {
                return new ConnectException("Client is not connected!");
            }

            try {

                $self->getLogger()->debug("SEND: packet length " . strlen($packet));

                yield $self->clientHandler->write($packet);

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::sendPacket exception!", [
                    'exception' => $e,
                ]);

                $self->disconnect();

                return new Failure($e);
            }

        }, $this, $packet);
    }

    /**
     * @return Promise<void>
     */
    protected function _handle(): Promise
    {
        return call(static function (self &$self) {

            if (empty($self->clientHandler) || $self->clientHandler->isClosed()) {
                $self->disconnect();
                return;
            }

            $packet = yield $self->clientHandler->read();

            if ($packet) {

                $self->getLogger()->debug("RECV: packet length " . strlen($packet));

                yield $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_RECEIVED($self, $packet));

            }

            //yield new Delayed(0);

        }, $this);
    }

    /**
     * @param SocketAddress $address
     * @param ConnectContext|null $connectContext
     *
     * @return Promise<ResourceSocket>
     *
     * @throws ConnectException
     * @throws CancelledException
     */
    protected function createConnectionHandler(SocketAddress $address, ?ConnectContext $connectContext = null): Promise
    {
        return Socket\connect("tcp://{$address}", $connectContext);;
    }
}