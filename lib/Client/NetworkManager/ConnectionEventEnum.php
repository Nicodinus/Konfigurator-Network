<?php


namespace Konfigurator\Network\Client\NetworkManager;


use Konfigurator\Common\Enums\EventEnum;

/**
 * Class ConnectionEventEnum
 * @package Konfigurator\Network\Client\NetworkManager
 * @method static static CONNECTED()
 * @method static static DISCONNECTED()
 * @method static static PACKET_RECEIVED()
 */
class ConnectionEventEnum extends EventEnum
{
    private const CONNECTED = 'connected';
    private const DISCONNECTED = 'disconnected';
    private const PACKET_RECEIVED = 'packet_received';
}