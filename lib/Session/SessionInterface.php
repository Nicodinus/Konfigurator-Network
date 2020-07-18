<?php


namespace Konfigurator\Network\Session;


interface SessionInterface
{
    /**
     * @return string|float|int|bool|null
     */
    public function getId();

    /**
     * @param string $packet
     * @return void
     */
    public function handlePacket(string $packet): void;

    /**
     * @return void
     */
    public function onConnected(): void;

    /**
     * @return void
     */
    public function onDisconnected(): void;
}