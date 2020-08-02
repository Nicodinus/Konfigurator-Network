<?php


namespace Konfigurator\Network\Client;


use Amp\CancelledException;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerInterface;

interface ClientNetworkHandlerInterface extends NetworkHandlerInterface
{
    /**
     * @param ResourceSocket $connection
     * @param NetworkEventDispatcher $eventDispatcher
     * @return static
     */
    public static function fromServerConnection(ResourceSocket $connection, NetworkEventDispatcher $eventDispatcher): self;

    /**
     * @param SocketAddress $address
     * @param ConnectContext|null $connectContext
     * @return Promise<void>|Failure<ConnectException|CancelledException>
     */
    public function connect(SocketAddress $address, ?ConnectContext $connectContext = null): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>|Failure<ConnectException>
     */
    public function sendPacket($packet): Promise;

    /**
     * @return SocketAddress|null
     */
    public function getAddress(): ?SocketAddress;
}