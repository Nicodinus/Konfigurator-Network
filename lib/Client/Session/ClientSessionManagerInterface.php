<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Promise;
use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
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
     * @param string|\Stringable $packet
     * @return Promise<void>
     */
    public function sendPacket($packet): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;
}