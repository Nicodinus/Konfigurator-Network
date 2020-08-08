<?php


namespace Konfigurator\Network\Session;


use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Client\ClientNetworkHandlerEvent;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerState;
use Konfigurator\Network\Packet\PacketHandlerInterface;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Session\Auth\AuthGuardInterface;
use function Amp\call;

abstract class AbstractSession implements SessionInterface, ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var ClientNetworkHandlerInterface */
    private ClientNetworkHandlerInterface $networkHandler;

    /** @var PacketHandlerInterface */
    private PacketHandlerInterface $packetHandler;

    /** @var SessionStorageInterface */
    private SessionStorageInterface $storage;

    /** @var AuthGuardInterface */
    private AuthGuardInterface $authGuard;

    /** @var NetworkEventDispatcher */
    private NetworkEventDispatcher $eventDispatcher;

    /** @var array<string, Deferred[]> */
    private array $packetListeners = [];

    /** @var Deferred[] */
    private array $anyPacketListeners = [];


    /**
     * AbstractClientSession constructor.
     * @param ClientNetworkHandlerInterface $networkHandler
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(ClientNetworkHandlerInterface $networkHandler, NetworkEventDispatcher $eventDispatcher)
    {
        $this->networkHandler = $networkHandler;
        $this->eventDispatcher = $eventDispatcher;

        $this->storage = $this->createStorage();
        $this->authGuard = $this->createAuthGuard();
        $this->packetHandler = $this->createPacketHandler();

        $this->getAuthGuard()->restoreAuth();

        $self = &$this;

        $this->eventDispatcher
            ->addListener(ClientNetworkHandlerEvent::DISCONNECTED(), function () use (&$self) {
                foreach ($self->packetListeners as $arr) {
                    /** @var Deferred $defer */
                    foreach ($arr as $defer) {
                        $defer->resolve();
                    }
                }
                $self->packetListeners = [];
                foreach ($self->anyPacketListeners as $defer) {
                    $defer->resolve();
                }
                $self->anyPacketListeners = [];
            })
        ;
    }

    /**
     * @return bool
     */
    public function isAlive(): bool
    {
        return $this->getNetworkHandler()->getState()->equals(NetworkHandlerState::RUNNING());
    }

    /**
     * @return AuthGuardInterface
     */
    public function getAuthGuard(): AuthGuardInterface
    {
        return $this->authGuard;
    }

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface
    {
        return $this->storage;
    }

    /**
     * @return SocketAddress
     */
    public function getAddress(): SocketAddress
    {
        return $this->networkHandler->getAddress();
    }

    /**
     * @param PacketInterface $packet
     *
     * @return Promise<void>|Failure<\Throwable>
     */
    public function sendPacket(PacketInterface $packet): Promise
    {
        return call(static function (self &$self) use ($packet) {

            try {

                $self->getLogger()->debug("SEND packet: " . get_class($packet));

                return yield $self->getNetworkHandler()->sendPacket(
                    yield $self->getPacketHandler()->preparePacket($packet)
                );

            } catch (\Throwable $e) {

                $self->getLogger()->warning("Sending packet error!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

        }, $this);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        $this->getNetworkHandler()->disconnect();
    }

    /**
     * @param mixed $id
     * @return Promise<PacketInterface|null>|Failure<\Throwable>
     */
    public function awaitPacket($id): Promise
    {
        if (!$this->getPacketHandler()->isPacketExist($id)) {
            return new Failure(new \LogicException("Invalid packet id {$id}!"));
        }

        if (!isset($this->packetListeners[$id])) {
            $this->packetListeners[$id] = [];
        }

        $defer = new Deferred();

        $this->packetListeners[$id][] = $defer;

        return $defer->promise();
    }

    /**
     * @return Promise<PacketInterface|null>
     */
    public function awaitAnyPacket(): Promise
    {
        $defer = new Deferred();

        $this->anyPacketListeners[] = $defer;

        return $defer->promise();
    }

    /**
     * @param string $packet
     * @return Promise<void>
     */
    public function handlePacket(string $packet): Promise
    {
        return call(static function (self &$self, string $packet) {

            try {

                $packet = yield $self->getPacketHandler()->handlePacket($self, $packet);

                $self->getLogger()->debug("RECV packet: " . get_class($packet));

                $self->notifyPacketListeners($packet);

                yield $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_HANDLED($self->getNetworkHandler(), $packet));

                $response = yield $self->handleReceivedPacket($packet);
                if (!empty($response)) {
                    yield $self->sendPacket($response);
                }

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this, $packet);
    }

    /**
     * @param PacketInterface $packet
     * @return void
     */
    protected function notifyPacketListeners(PacketInterface $packet): void
    {
        if (isset($this->packetListeners[$packet::getId()])) {

            while ($listener = array_shift($this->packetListeners[$packet::getId()])) {
                $listener->resolve($packet);
            }

        }

        while ($listener = array_shift($this->anyPacketListeners)) {
            $listener->resolve($packet);
        }
    }

    /**
     * @return NetworkEventDispatcher
     */
    protected function getEventDispatcher(): NetworkEventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return SessionStorageInterface
     */
    protected function createStorage(): SessionStorageInterface
    {
        return new SessionStorage($this);
    }

    /**
     * @return PacketHandlerInterface
     */
    protected function getPacketHandler(): PacketHandlerInterface
    {
        return $this->packetHandler;
    }

    /**
     * @return ClientNetworkHandlerInterface
     */
    protected function getNetworkHandler(): ClientNetworkHandlerInterface
    {
        return $this->networkHandler;
    }

    /**
     * @param mixed $id
     * @param mixed ...$args
     *
     * @return PacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket($id, ...$args): PacketInterface
    {
        if (!$this->getPacketHandler()->isPacketExist($id)) {
            throw new \LogicException("Invalid packet id {$id}!");
        }

        return $this->getPacketHandler()->createPacket($this, $id, ...$args);
    }

    /**
     * @return AuthGuardInterface
     */
    protected abstract function createAuthGuard(): AuthGuardInterface;

    /**
     * @return PacketHandlerInterface
     */
    protected abstract function createPacketHandler(): PacketHandlerInterface;

    /**
     * @param PacketInterface $packet
     * @return Promise<PacketInterface|null>
     */
    protected abstract function handleReceivedPacket(PacketInterface $packet): Promise;
}