<?php


namespace Konfigurator\Network\Session;


use Amp\Deferred;
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
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise
    {
        $this->getLogger()->debug("SEND packet: " . get_class($packet));

        return $this->getNetworkHandler()->sendPacket(
            $this->getPacketHandler()->preparePacket($packet)
        );
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        $this->getNetworkHandler()->disconnect();
    }

    /**
     * @param string|PacketInterface $packet
     * @return Promise
     */
    public function awaitPacket($packet): Promise
    {
        if (!is_string($packet)) {

            if (array_search($packet, class_implements(PacketInterface::class)) === false) {
                throw new \LogicException("Invalid class!");
            }

            $packet = get_class($packet);
        }

        if (!isset($this->packetListeners[$packet])) {
            $this->packetListeners[$packet] = [];
        }

        $defer = new Deferred();

        $this->packetListeners[$packet][] = $defer;

        return $defer->promise();
    }

    /**
     * @return Promise
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

            $packet = yield $self->getPacketHandler()->handlePacket($self, $packet);

            $self->getLogger()->debug("RECV packet: " . get_class($packet));

            $self->notifyPacketAwaiters($packet);

            $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_HANDLED($self->getNetworkHandler(), $packet));

            $response = yield $self->handleReceivedPacket($packet);
            if (!empty($response)) {
                yield $self->sendPacket($response);
            }

        }, $this, $packet);
    }

    /**
     * @param PacketInterface $packet
     * @return void
     */
    protected function notifyPacketAwaiters(PacketInterface $packet): void
    {
        if (isset($this->packetListeners[get_class($packet)])) {

            while ($listener = array_shift($this->packetListeners[get_class($packet)])) {
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
     * @param string $classname
     * @param $args
     * @return PacketInterface
     */
    public function createPacket(string $classname, ...$args): PacketInterface
    {
        return new $classname($this, false, ...$args);
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