<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Promise;
use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packets\PacketHandlerInterface;
use Konfigurator\Network\Packets\PacketInterface;
use Konfigurator\Network\Session\SessionManagerInterface;

interface ClientSessionManagerInterface extends SessionManagerInterface
{
    /**
     * @return ClientSessionInterface
     */
    public function getClientSession(): ClientSessionInterface;

    /**
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface;

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