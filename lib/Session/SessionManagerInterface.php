<?php


namespace Konfigurator\Network\Session;


use Amp\Promise;
use Konfigurator\Network\NetworkManagerInterface;

interface SessionManagerInterface
{
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

    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface;
}