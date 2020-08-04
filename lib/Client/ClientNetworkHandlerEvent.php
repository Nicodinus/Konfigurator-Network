<?php


namespace Konfigurator\Network\Client;


use Konfigurator\Network\NetworkHandlerEvent;
use Konfigurator\Network\NetworkHandlerInterface;
use Konfigurator\Network\Packet\PacketInterface;


/**
 * Class ClientNetworkHandlerEvent
 * @package Konfigurator\Network\Client
 * @method static static CONNECTED(ClientNetworkHandlerInterface $networkHandler = null)
 * @method static static DISCONNECTED(ClientNetworkHandlerInterface $networkHandler = null)
 * @method static static PACKET_RECEIVED(ClientNetworkHandlerInterface $networkHandler = null, string $packet = null)
 * @method static static PACKET_HANDLED(ClientNetworkHandlerInterface $networkHandler = null, PacketInterface $packet = null)
 */
class ClientNetworkHandlerEvent extends NetworkHandlerEvent
{
    protected const CONNECTED = 'connected';
    protected const DISCONNECTED = 'disconnected';
    protected const PACKET_RECEIVED = 'packet_received';
    protected const PACKET_HANDLED = 'packet_handled';

    /**
     * @return ClientNetworkHandlerInterface|null
     */
    public function getNetworkHandler(): ?NetworkHandlerInterface
    {
        return parent::getNetworkHandler();
    }
}