<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface PacketInterface
{
    /**
     * @return mixed
     */
    public static function getId();

    /**
     * @return bool
     */
    public function isRemote(): bool;

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;


    /**
     * @return Promise<string>|Failure<\Throwable>
     */
    public function transform(): Promise;
}