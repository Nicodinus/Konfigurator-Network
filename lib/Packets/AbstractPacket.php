<?php


namespace Konfigurator\Network\Packets;


use Konfigurator\Network\Session\SessionInterface;

abstract class AbstractPacket implements PacketInterface
{
    /** @var SessionInterface */
    protected SessionInterface $session;

    /** @var bool */
    protected bool $isRemote;

    /** @var mixed */
    protected $data;


    /**
     * AbstractBasicPacket constructor.
     * @param SessionInterface $session
     * @param bool $isRemote
     */
    public function __construct(SessionInterface $session, bool $isRemote = false)
    {
        $this->session = $session;
        $this->isRemote = $isRemote;
        $this->data = null;
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

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    protected abstract function encode(): string;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->encode();
    }
}