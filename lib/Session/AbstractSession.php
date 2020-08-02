<?php


namespace Konfigurator\Network\Session;


use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
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


    /**
     * AbstractClientSession constructor.
     * @param ClientNetworkHandlerInterface $networkHandler
     */
    public function __construct(ClientNetworkHandlerInterface $networkHandler)
    {
        $this->networkHandler = $networkHandler;

        $this->storage = $this->createStorage();
        $this->authGuard = $this->createAuthGuard();
        $this->packetHandler = $this->createPacketHandler();

        $this->getAuthGuard()->restoreAuth();
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
     * @param string $packet
     * @return Promise<void>
     */
    public function handlePacket(string $packet): Promise
    {
        return call(static function (self &$self, string $packet) {

            $packet = yield $self->getPacketHandler()->handlePacket($self, $packet);

            $self->getLogger()->debug("RECV packet: " . get_class($packet));

            $response = yield $self->handleReceivedPacket($packet);
            if (!empty($response)) {
                yield $self->sendPacket($response);
            }

        }, $this, $packet);
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