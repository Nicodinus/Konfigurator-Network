<?php


namespace Konfigurator\Network;


use Konfigurator\Common\Interfaces\AsyncHandlerInterface;
use Konfigurator\Common\Interfaces\ClassHasEvents;
use Konfigurator\Common\Interfaces\ClassHasState;
use Konfigurator\Common\Interfaces\GracefulShutdownPossible;

interface NetworkManagerInterface extends AsyncHandlerInterface,
    ClassHasState, ClassHasEvents, GracefulShutdownPossible
{
    //
}