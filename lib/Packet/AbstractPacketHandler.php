<?php


namespace Konfigurator\Network\Packet;


abstract class AbstractPacketHandler implements PacketHandlerInterface
{
    /** @var PacketRepositoryInterface */
    private PacketRepositoryInterface $packetRepository;


    /**
     * JsonPacketHandler constructor.
     * @param PacketRepositoryInterface $packetRepository
     */
    public function __construct(PacketRepositoryInterface $packetRepository)
    {
        $this->packetRepository = $packetRepository;
    }

    /**
     * @return PacketRepositoryInterface
     */
    public function getPacketRepository(): PacketRepositoryInterface
    {
        return $this->packetRepository;
    }
}