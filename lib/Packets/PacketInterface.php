<?php


namespace Konfigurator\Network\Packets;


use Konfigurator\Network\Session\SessionInterface;

interface PacketInterface extends \Stringable
{
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