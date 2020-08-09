<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

abstract class AbstractPacketRepository implements PacketRepositoryInterface
{
    /** @var InputPacketInterface[]|string[] */
    protected array $inputPacketsRegistry = [];

    /** @var OutputPacketInterface[]|string[] */
    protected array $outputPacketsRegistry = [];


    /**
     * AbstractPacketRepository constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param mixed $inputPacketId
     *
     * @return InputPacketInterface|string|null
     */
    public function findInputPacket($inputPacketId): ?string
    {
        if (!isset($this->inputPacketsRegistry[$inputPacketId])) {
            return null;
        }

        return $this->inputPacketsRegistry[$inputPacketId];
    }

    /**
     * @param SessionInterface $session
     * @param mixed $outputPacketId
     * @param mixed ...$args
     *
     * @return OutputPacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket(SessionInterface $session, $outputPacketId, ...$args): OutputPacketInterface
    {
        if (!$this->isPacketExist($outputPacketId)) {
            throw new \LogicException("Can't find output packet id {$outputPacketId}!");
        }

        return $this->outputPacketsRegistry[$outputPacketId]::create($session, ...$args);
    }

    /**
     * @param mixed $outputPacketId
     *
     * @return bool
     */
    public function isPacketExist($outputPacketId): bool
    {
        return isset($this->outputPacketsRegistry[$outputPacketId]);
    }
}