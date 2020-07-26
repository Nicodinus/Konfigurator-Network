<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string $packet
     * @return PacketInterface
     */
    public function handlePacket($session, string $packet): PacketInterface;

    /**
     * @param PacketInterface $packet
     * @return string
     */
    public function preparePacket($packet): string;
}