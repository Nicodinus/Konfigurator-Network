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
    private const RUNNING = 'running';
    private const STOPPED = 'stopped';
}