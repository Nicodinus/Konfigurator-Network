<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string $classname
     * @param bool $isRemote
     * @return PacketInterface
     */
    public function createPacket($session, string $classname, bool $isRemote = false): PacketInterface;

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