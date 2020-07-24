<?php


namespace Konfigurator\Network;


use Konfigurator\Common\AbstractAsyncHandler;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasEventsTrait;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Common\Traits\ClassHasStateTrait;

abstract class AbstractNetworkManager extends AbstractAsyncHandler implements NetworkManagerInterface, ClassHasLogger
{
    use ClassHasLoggerTrait, ClassHasStateTrait, ClassHasEventsTrait;


    /**
     * AbstractNetworkManager constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param \Throwable $exception
     * @param string|null $message
     */
    protected function exceptionLoopHandler(\Throwable $exception, ?string $message = null): void
    {
        $this->getLogger()->error($message ?? __CLASS__ . " throws an exception!", [
            'exception' => $exception,
        ]);
    }
}