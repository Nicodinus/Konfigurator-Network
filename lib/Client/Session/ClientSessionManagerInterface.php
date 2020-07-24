<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Promise;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Session\SessionManagerInterface;

interface ClientSessionManagerInterface extends SessionManagerInterface
{
    /**
     * @return ClientSessionInterface
     */
    public function getClientSession(): ClientSessionInterface;

    /**
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;
}