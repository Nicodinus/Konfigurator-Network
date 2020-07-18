<?php


namespace Konfigurator\Network\Packets;


use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string|PacketInterface $classname
     * @return PacketInterface
     */
    public function createPacket(SessionInterface $session, string $classname): PacketInterface;

    /**
     * @param SessionInterface $session
     * @param string|null $packet
     * @return PacketInterface
     */
    public function handleRemotePacket(SessionInterface $session, ?string $packet = null): PacketInterface;

    /**
     * @param PacketInterface $packet
     * @return string
     */
    public function handleLocalPacket(PacketInterface $packet): string;
}