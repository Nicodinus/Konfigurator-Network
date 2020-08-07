<?php


namespace Konfigurator\Network\Packet;


use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string $packet
     * @return Promise<PacketInterface>
     */
    public function handlePacket(SessionInterface $session, string $packet): Promise;

    /**
     * @param PacketInterface $packet
     * @return Promise<string>
     */
    public function preparePacket(PacketInterface $packet): Promise;

    /**
     * @param SessionInterface $session
     * @param string $classname
     * @return PacketInterface|null
     */
    public function createPacket(SessionInterface $session, string $classname): ?PacketInterface;

    /**
     * @param string $id
     * @return string|PacketInterface|null
     */
    public function getPacketClass(string $id): ?string;
}