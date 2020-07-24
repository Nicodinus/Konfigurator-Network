<?php


namespace Konfigurator\Network\Session;


use Konfigurator\Common\Interfaces\AsyncHandlerInterface;
use Konfigurator\Common\Interfaces\GracefulShutdownPossible;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packet\PacketHandlerInterface;

interface SessionManagerInterface extends AsyncHandlerInterface, GracefulShutdownPossible
{
    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface;

    /**
     * @return PacketHandlerInterface
     */
    public function getPacketHandler(): PacketHandlerInterface;
}