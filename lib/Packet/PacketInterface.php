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
     * @param SessionInterface $session
     * @param mixed ...$args
     *
     * @return static
     */
    public static function create(SessionInterface $session, ...$args);

    /**
     * @param SessionInterface $session
     * @param mixed ...$args
     *
     * @return static
     */
    public static function fromRemote(SessionInterface $session, ...$args);

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