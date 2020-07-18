<?php


namespace Konfigurator\Network\Session;


use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Network\NetworkManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractSessionManager implements SessionManagerInterface, ClassHasLogger
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var NetworkManagerInterface */
    private NetworkManagerInterface $networkManager;

    /** @var bool */
    private bool $isShutdownPending;


    /**
     * AbstractSessionManager constructor.
     * @param NetworkManagerInterface $networkManager
     */
    public function __construct(NetworkManagerInterface $networkManager)
    {
        $this->logger = new NullLogger();
        $this->networkManager = $networkManager;
        $this->isShutdownPending = false;
    }

    /**
     * @param LoggerInterface $logger
     * @return static
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        if ($this->isShutdownPending()) {
            return;
        }

        $this->isShutdownPending = true;
    }

    /**
     * @return bool
     */
    public function isShutdownPending(): bool
    {
        return $this->isShutdownPending;
    }

    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return $this->networkManager;
    }
}