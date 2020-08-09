<?php


namespace Konfigurator\Network\Session;


use Amp\Failure;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\Packet\InputPacketInterface;
use Konfigurator\Network\Packet\OutputPacketInterface;
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
     * @param mixed $inputPacketId
     *
     * @return Promise<InputPacketInterface|null>|Failure<\Throwable>
     */
    public function awaitPacket($inputPacketId): Promise;

    /**
     * @return Promise<InputPacketInterface|null>
     */
    public function awaitAnyPacket(): Promise;

    /**
     * @param InputPacketInterface $inputPacket
     *
     * @return Promise<void>
     */
    public function handlePacket(InputPacketInterface $inputPacket): Promise;

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface;

    /**
     * @return AuthGuardInterface
     */
    public function getAuthGuard(): AuthGuardInterface;

    /**
     * @param mixed $outputPacketId
     * @param mixed ...$args
     *
     * @return OutputPacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket($outputPacketId, ...$args): OutputPacketInterface;

    /**
     * @param OutputPacketInterface $outputPacket
     *
     * @return Promise<void>|Failure<\Throwable>
     */
    public function sendPacket(OutputPacketInterface $outputPacket): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;
}