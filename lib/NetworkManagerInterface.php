<?php


namespace Konfigurator\Network;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Common\Enums\EventEnum;
use Konfigurator\Common\Enums\StateEnum;
use Konfigurator\Common\Exceptions\PendingShutdownError;

interface NetworkManagerInterface
{
    /**
     * @return StateEnum
     */
    public function getState(): StateEnum;

    /**
     * @return Promise<EventEnum|null>|Failure<PendingShutdownError>
     */
    public function awaitEvent(): Promise;

    /**
     * @return Promise<void>
     */
    public function handle(): Promise;

    /**
     * @return void
     */
    public function shutdown(): void;

    /**
     * @return bool
     */
    public function isShutdownPending(): bool;
}