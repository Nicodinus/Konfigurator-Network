<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;
use Konfigurator\Common\Enums\AccessLevelEnum;

interface PacketInterface
{
    /**
     * @return mixed
     */
    public static function getId();

    /**
     * @return AccessLevelEnum|null
     */
    public static function accessRequired(): ?AccessLevelEnum;

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;
}