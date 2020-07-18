<?php


namespace Konfigurator\Network\Session;


use Amp\Promise;
use Konfigurator\Network\Packets\PacketInterface;

interface SessionInterface
{
    /**
     * @return string|float|int|bool|null
     */
    public function getId();

    /**
     * @param PacketInterface $packet
     * @return void
     */
    public function handle(PacketInterface $packet): void;

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface;

    /**
     * @return SessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface;

    /**
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise;

    /**
     * @param string $classname
     * @return PacketInterface
     */
    public function createPacket(string $classname): PacketInterface;
}