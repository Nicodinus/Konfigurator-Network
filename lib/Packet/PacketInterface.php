<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

interface PacketInterface
{
    /**
     * @return mixed
     */
    public static function getId();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return bool
     */
    public function isRemote(): bool;

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;
}