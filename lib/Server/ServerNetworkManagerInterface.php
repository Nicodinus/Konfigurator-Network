<?php


namespace Konfigurator\Network\Server;


use Amp\Failure;
use Amp\Promise;
use Amp\Socket\ResourceSocket;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\NetworkManagerInterface;


interface ServerNetworkManagerInterface extends NetworkManagerInterface
{
    /**
     * @param SocketAddress $address
     * @return Promise<void>|Failure<\Throwable>
     */
    public function listen(SocketAddress $address): Promise;

    /**
     * @return void
     */
    public function close(): void;

    /**
     * @param SocketAddress $address
     * @return ResourceSocket|null
     */
    public function getClientSocket(SocketAddress $address): ?ResourceSocket;

    /**
     * @param SocketAddress $remoteAddr
     * @param string $packet
     * @return Promise<void>
     */
    public function sendPacket(SocketAddress $remoteAddr, string $packet): Promise;

    /**
     * @param SocketAddress $remoteAddr
     * @return Promise<void>
     */
    public function disconnect(SocketAddress $remoteAddr): Promise;
}