<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Promise;
use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Session\AbstractSession;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractClientSession extends AbstractSession implements ClientSessionInterface
{
    /**
     * AbstractClientSession constructor.
     * @param ClientSessionManagerInterface $sessionManager
     */
    public function __construct(ClientSessionManagerInterface $sessionManager)
    {
        parent::__construct($sessionManager);
    }

    /**
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise
    {
        return $this->getSessionManager()->sendPacket($packet);
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
     * @return ClientSessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return parent::getSessionManager();
    }
}