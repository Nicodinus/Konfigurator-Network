<?php


namespace Konfigurator\Network\Packet;


use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface InputPacketInterface extends PacketInterface
{
    /**
     * @param SessionInterface $session
     * @param mixed $inputPacket
     *
     * @return Promise<static>
     */
    public static function fromRemote(SessionInterface $session, $inputPacket): Promise;
}