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
    public function handlePacket($session, string $packet): Promise;

    /**
     * @param PacketInterface $packet
     * @return Promise<string>
     */
    public function preparePacket($packet): Promise;
}