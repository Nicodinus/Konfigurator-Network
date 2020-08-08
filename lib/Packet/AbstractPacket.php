<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

abstract class AbstractPacket implements PacketInterface
{
    /** @var SessionInterface */
    private SessionInterface $session;

    /** @var bool */
    private bool $isRemote;


    /**
     * AbstractBasicPacket constructor.
     * @param SessionInterface $session
     * @param bool $isRemote
     */
    public function __construct(SessionInterface $session, bool $isRemote = false)
    {
        $this->session = $session;
        $this->isRemote = $isRemote;
    }

    /**
     * @return bool
     */
    public function isRemote(): bool
    {
        return $this->isRemote;
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }
}