<?php


namespace Konfigurator\Network\Server\NetworkManager;


use Amp\Socket\SocketAddress;
use Konfigurator\Common\Enums\EventEnum;


/**
 * Class ClientEventEnum
 * @package Konfigurator\Network\Server\NetworkManager
 * @method static static CONNECTED()
 * @method static static DISCONNECTED()
 * @method static static PACKET_RECEIVED()
 */
class ClientEventEnum extends EventEnum
{
    private const CONNECTED = 'connected';
    private const DISCONNECTED = 'disconnected';
    private const PACKET_RECEIVED = 'packet_received';

    /** @var SocketAddress|null */
    protected ?SocketAddress $remoteAddress = null;

    /**
     * @param SocketAddress $remoteAddress
     * @return static
     */
    public function setRemoteAddress(SocketAddress $remoteAddress): self
    {
        $this->remoteAddress = $remoteAddress;
        return $this;
    }

    /**
     * @return SocketAddress|null
     */
    public function getRemoteAddress(): ?SocketAddress
    {
        return $this->remoteAddress;
    }
}