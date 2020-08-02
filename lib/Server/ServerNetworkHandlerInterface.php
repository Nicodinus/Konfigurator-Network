<?php


namespace Konfigurator\Network\Server;


use Amp\Failure;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
use Konfigurator\Network\NetworkHandlerInterface;


interface ServerNetworkHandlerInterface extends NetworkHandlerInterface
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
     * @return ClientNetworkHandlerInterface|null
     */
    public function getClientHandler(SocketAddress $address): ?ClientNetworkHandlerInterface;
}