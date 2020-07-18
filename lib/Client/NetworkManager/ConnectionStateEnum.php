<?php


namespace Konfigurator\Network\Client\NetworkManager;


use Konfigurator\Common\Enums\StateEnum;


/**
 * Class ConnectionStateEnum
 * @package Konfigurator\Network\Client\NetworkManager
 * @method static static UNDEFINED()
 * @method static static CLOSED()
 * @method static static CONNECTED()
 */
class ConnectionStateEnum extends StateEnum
{
    private const CLOSED = 'closed';
    private const CONNECTED = 'connected';
}