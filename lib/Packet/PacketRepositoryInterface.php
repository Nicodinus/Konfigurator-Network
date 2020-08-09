<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

interface PacketRepositoryInterface
{
    /**
     * @param mixed $inputPacketId
     *
     * @return InputPacketInterface|string|null
     */
    public function findInputPacket($inputPacketId): ?string;

    /**
     * @param SessionInterface $session
     * @param mixed $outputPacketId
     * @param mixed ...$args
     *
     * @return OutputPacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket(SessionInterface $session, $outputPacketId, ...$args): OutputPacketInterface;

    /**
     * @param mixed $outputPacketId
     *
     * @return bool
     */
    public function isPacketExist($outputPacketId): bool;
}