<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;

interface PacketHandlerInterface
{
    /**
     * @param SessionInterface $session
     * @param string $rawPacket
     *
     * @return Promise<PacketInterface>|Failure<\Throwable>
     */
    public function handlePacket(SessionInterface $session, string $rawPacket): Promise;

    /**
     * @param SessionInterface $session
     * @param mixed $id
     * @param mixed ...$args
     *
     * @return PacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket(SessionInterface $session, $id, ...$args): PacketInterface;

    /**
     * @param mixed $id
     *
     * @return bool
     */
    public function isPacketExist($id): bool;
}