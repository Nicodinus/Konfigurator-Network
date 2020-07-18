<?php


namespace Konfigurator\Network\Packets;


use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string $packet
     * @return PacketInterface
     */
    public function handleRemotePacket(SessionInterface $session, string $packet);
}