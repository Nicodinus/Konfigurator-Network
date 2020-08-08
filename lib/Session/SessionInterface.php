<?php


namespace Konfigurator\Network\Session;


use Amp\Failure;
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
     * @param mixed $id
     *
     * @return Promise<PacketInterface|null>|Failure<\Throwable>
     */
    public function awaitPacket($id): Promise;

    /**
     * @return Promise<PacketInterface|null>
     */
    public function awaitAnyPacket(): Promise;

    /**
     * @param string $packet
     *
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
     * @param mixed $id
     * @param mixed ...$args
     *
     * @return PacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket($id, ...$args): PacketInterface;

    /**
     * @param PacketInterface $packet
     *
     * @return Promise<void>|Failure<\Throwable>
     */
    public function sendPacket(PacketInterface $packet): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;
}