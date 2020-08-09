<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @return PacketRepositoryInterface
     */
    public function getPacketRepository(): PacketRepositoryInterface;

    /**
     * @param SessionInterface $session
     * @param mixed $inputPacket
     *
     * @return Promise<InputPacketInterface>|Failure<\Throwable>
     */
    public function handlePacket(SessionInterface $session, $inputPacket): Promise;

    /**
     * @param OutputPacketInterface $outputPacket
     *
     * @return Promise<string>|Failure<\Throwable>
     */
    public function transform(OutputPacketInterface $outputPacket): Promise;
}