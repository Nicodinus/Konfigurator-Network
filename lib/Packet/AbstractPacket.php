<?php


namespace Konfigurator\Network\Packet;


use Konfigurator\Network\Session\SessionInterface;

abstract class AbstractPacket implements PacketInterface
{
    /** @var SessionInterface */
    private SessionInterface $session;


    /**
     * AbstractPacket constructor.
     * @param SessionInterface $session
     */
    protected function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }
}