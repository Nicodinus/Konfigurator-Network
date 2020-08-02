<?php


namespace Konfigurator\Network;


use Konfigurator\Common\Interfaces\AsyncHandlerInterface;
use Konfigurator\Common\Interfaces\ClassHasState;
use Konfigurator\Common\Interfaces\GracefulShutdownPossible;

interface NetworkHandlerInterface extends AsyncHandlerInterface, GracefulShutdownPossible, ClassHasState
{
    /**
     * @return NetworkHandlerState
     */
    public function getState(): NetworkHandlerState;
}