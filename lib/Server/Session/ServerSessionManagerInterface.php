<?php


namespace Konfigurator\Network\Server\Session;


use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packets\PacketHandlerInterface;
use Konfigurator\Network\Packets\PacketInterface;
use Konfigurator\Network\Server\ServerNetworkManagerInterface;
use Konfigurator\Network\Session\SessionManagerInterface;


interface ServerSessionManagerInterface extends SessionManagerInterface
{
    /**
     * @param SocketAddress $address
     * @return ServersideClientSessionInterface|null
     */
    public function getClientSession(SocketAddress $address): ?ServersideClientSessionInterface;

    /**
     * @return ServerNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface;

    /**
     * @param SocketAddress $address
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(SocketAddress $address, PacketInterface $packet): Promise;

    /**
     * @param SocketAddress $address
     * @return void
     */
    public function disconnect(SocketAddress $address): void;
}