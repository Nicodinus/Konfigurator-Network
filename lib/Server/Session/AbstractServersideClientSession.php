<?php


namespace Konfigurator\Network\Server\Session;


use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Server\ServerNetworkManagerInterface;
use Konfigurator\Network\Session\AbstractSession;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractServersideClientSession extends AbstractSession implements ServersideClientSessionInterface
{
    /** @var SocketAddress */
    protected SocketAddress $remoteAddress;


    /**
     * ClientSession constructor.
     * @param ServerSessionManagerInterface $sessionManager
     * @param SocketAddress $remoteAddress
     */
    public function __construct(ServerSessionManagerInterface $sessionManager, SocketAddress $remoteAddress)
    {
        parent::__construct($sessionManager);

        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @return SocketAddress
     */
    public function getRemoteAddress(): SocketAddress
    {
        return $this->remoteAddress;
    }

    /**
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise
    {
        return $this->getSessionManager()->sendPacket($this->remoteAddress, $packet);
    }

    /**
     * @param string $classname
     * @return PacketInterface
     */
    public function createPacket(string $classname): PacketInterface
    {
        return $this->getSessionManager()->getPacketHandler()->createPacket($this, $classname);
    }

    /**
     * @return ServerNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return ServerSessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return parent::getSessionManager();
    }
}