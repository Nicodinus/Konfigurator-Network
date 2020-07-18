<?php


namespace Konfigurator\Network\Server\NetworkManager;


use Konfigurator\Common\Enums\StateEnum;


/**
 * Class ServerStateEnum
 * @package Konfigurator\Network\Server\NetworkManager
 * @method static static UNDEFINED()
 * @method static static CLOSED()
 * @method static static LISTEN()
 */
class ServerStateEnum extends StateEnum
{
    private const UNDEFINED = 'undefined';
    private const CLOSED = 'closed';
    private const LISTEN = 'listen';
}