<?php


namespace Konfigurator\Network\Session;


use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Session\Auth\AuthGuardInterface;

interface SessionInterface
{
    /**
     * @return SocketAddress
     */
    public function getAddress(): SocketAddress;

    /**
     * @return bool
     */
    public function isAlive(): bool;

    /**
     * @param string|PacketInterface $packet
     * @return Promise<PacketInterface>
     */
    public function awaitPacket($packet): Promise;

    /**
     * @param mixed $id
     * @return Promise<PacketInterface>
     */
    public function awaitPacketId($id): Promise;

    /**
     * @return Promise<PacketInterface>
     */
    public function awaitAnyPacket(): Promise;

    /**
     * @param string $packet
     * @return Promise<void>
     */
    public function handlePacket(string $packet): Promise;

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface;

    /**
     * @return AuthGuardInterface
     */
    public function getAuthGuard(): AuthGuardInterface;

    /**
     * @param string $classname
     * @param $args
     * @return PacketInterface
     */
    public function createPacket(string $classname, ...$args): PacketInterface;

    /**
     * @param mixed $id
     * @return PacketInterface|string|null
     */
    public function findPacketClassById($id): ?string;

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