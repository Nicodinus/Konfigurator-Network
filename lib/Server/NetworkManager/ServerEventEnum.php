<?php


namespace Konfigurator\Network\Server\NetworkManager;


use Konfigurator\Common\Enums\EventEnum;


/**
 * Class ConnectionEventEnum
 * @package Konfigurator\Network\Server\NetworkManager
 * @method static static LISTEN()
 * @method static static CLOSED()
 */
class ServerEventEnum extends EventEnum
{
    private const LISTEN = 'listen';
    private const CLOSED = 'closed';
}