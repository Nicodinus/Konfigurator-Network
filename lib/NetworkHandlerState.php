<?php


namespace Konfigurator\Network;


use Konfigurator\Common\Enums\StateEnum;

/**
 * Class NetworkHandlerState
 * @package Konfigurator\Network
 * @method static static RUNNING()
 * @method static static STOPPED()
 */
class NetworkHandlerState extends StateEnum
{
    protected const RUNNING = 'running';
    protected const STOPPED = 'stopped';
}