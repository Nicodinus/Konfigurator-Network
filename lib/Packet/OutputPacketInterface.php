<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface OutputPacketInterface extends PacketInterface
{
    /**
     * @param SessionInterface $session
     * @param mixed ...$args
     *
     * @return static
     */
    public static function create(SessionInterface $session, ...$args);

    /**
     * @return Promise<string>|Failure<\Throwable>
     */
    public function transform(): Promise;

    /**
     * @return Promise
     */
    public function send(): Promise;
}