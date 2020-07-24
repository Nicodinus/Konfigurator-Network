<?php


namespace Konfigurator\Network\Client;


use Amp\CancelledException;
use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Common\Enums\StateEnum;
use Konfigurator\Common\Exceptions\PendingShutdownError;
use Konfigurator\Network\AbstractNetworkManager;
use Konfigurator\Network\Client\NetworkManager\ConnectionEventEnum;
use Konfigurator\Network\Client\NetworkManager\ConnectionStateEnum;
use function Amp\asyncCall;
use function Amp\call;
use function Amp\Socket\connect;

class ClientNetworkManager extends AbstractNetworkManager implements ClientNetworkManagerInterface
{
    /** @var ResourceSocket|null */
    protected ?ResourceSocket $clientHandler;

    /**
     * ClientNetworkManager constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->clientHandler = null;
    }

    /**
     * @param SocketAddress $address
     * @param int $timeout
     * @return Promise<ResourceSocket>|Failure
     */
    protected function createConnectHandler(SocketAddress $address, int $timeout): Promise
    {
        return call(static function (self &$self, SocketAddress $address, int $timeout) {

            try {

                return new Success(yield connect("tcp://{$address}", (new ConnectContext())
                    ->withConnectTimeout($timeout)
                ));

            } catch (\Throwable $e) {

                return new Failure($e);

            }

        }, $this, $address, $timeout);
    }

    /**
     * @return ConnectionStateEnum
     */
    public function getState(): StateEnum
    {
        return parent::getState();
    }

    /**
     * @param SocketAddress $address
     * @param int $timeout
     * @return Promise<void>|Failure<ConnectException|CancelledException>
     */
    public function connect(SocketAddress $address, int $timeout = 1000): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        if ($this->getState()->equals(ConnectionStateEnum::CONNECTED())) {
            return new Success();
        }

        return call(static function (self &$self, SocketAddress $address, int $timeout) {

            try {

                $self->getLogger()->debug("Connecting to tcp://{$address}...");

                $self->clientHandler = yield $self->createConnectHandler($address, $timeout);

                $self->getLogger()->info("Connection successful established with tcp://{$address}!");

                asyncCall(static function (self &$self) {

                    while (!$self->isShutdownPending() && $self->clientHandler && !$self->clientHandler->isClosed()) {
                        yield new Delayed(1000);
                    }

                    if ($self->getState()->equals(ConnectionStateEnum::CONNECTED())) {
                        $self->disconnect();
                    }

                }, $self);

                $self->setState(ConnectionStateEnum::CONNECTED());

                $self->notifyEventAcceptor(ConnectionEventEnum::CONNECTED());

                yield new Delayed(0);

            } catch (\Throwable $e) {

                $self->setState(ConnectionStateEnum::CLOSED());

                $self->getLogger()->debug(__CLASS__ . "::connect exception!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

        }, $this, $address, $timeout);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        if (!$this->getState()->equals(ConnectionStateEnum::CONNECTED())) {
            return;
        }

        asyncCall(static function (self &$self) {

            $self->getLogger()->info("Connection closed!");

            if ($self->clientHandler) {
                if (!$self->clientHandler->isClosed()) {
                    $self->clientHandler->close();
                }
                $self->clientHandler = null;
            }

            $self->setState(ConnectionStateEnum::CLOSED());

            $self->notifyEventAcceptor(ConnectionEventEnum::DISCONNECTED());

            yield new Delayed(0);

        }, $this);
    }

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>|Failure<ConnectException>
     */
    public function sendPacket($packet): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        if (!$this->getState()->equals(ConnectionStateEnum::CONNECTED())) {
            return new Failure(new ConnectException("Client disconnected!"));
        }

        return call(static function (self &$self, $packet) {

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
    public function handle(): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        return call(static function (self &$self) {

            while ($self->getState()->equals(ConnectionStateEnum::CONNECTED())) {

                $packet = yield $self->receivePacket();
                if (!$packet) {
                    yield new Delayed(0);
                    continue;
                }

                $self->notifyEventAcceptor(ConnectionEventEnum::PACKET_RECEIVED()->withEventData($packet));

                yield new Delayed(0);
            }

        }, $this);
    }

    /**
     * @return Promise<string>|Failure<ConnectException>
     */
    public function receivePacket(): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        if (!$this->getState()->equals(ConnectionStateEnum::CONNECTED())) {
            return new Failure(new ConnectException("Client disconnected!"));
        }

        return call(static function (self &$self) {

            try {

                $packet = yield $self->clientHandler->read();
                if (!$packet) {
                    return new Success();
                }

                $self->getLogger()->debug("RECV: packet length " . strlen($packet));

                return new Success($packet);

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::receivePacket exception!", [
                    'exception' => $e,
                ]);

                $self->disconnect();

                return new Failure($e);
            }

        }, $this);
    }
}