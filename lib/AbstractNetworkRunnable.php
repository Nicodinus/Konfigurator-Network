<?php


namespace Konfigurator\Network;


use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use Konfigurator\Common\Interfaces\AppRunnableInterface;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Network\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractNetworkRunnable implements ClassHasLogger, AppRunnableInterface
{
    /** @var NetworkManagerInterface */
    private NetworkManagerInterface $networkManager;

    /** @var SessionManagerInterface */
    private SessionManagerInterface $sessionManager;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var bool */
    private bool $isShutdownPending;


    /**
     * AbstractNetworkRunnable constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->isShutdownPending = false;

        $this->networkManager = $this->createNetworkManager();
        $this->sessionManager = $this->createSessionManager($this->networkManager);
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
     * @param NetworkManagerInterface $networkManager
     * @return SessionManagerInterface
     */
    protected abstract function createSessionManager($networkManager): SessionManagerInterface;

    /**
     * @return NetworkManagerInterface
     */
    protected abstract function createNetworkManager(): NetworkManagerInterface;

    /**
     * @return SessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return $this->sessionManager;
    }

    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return $this->networkManager;
    }

    /**
     * @return bool
     */
    public function isShutdownPending(): bool
    {
        return $this->isShutdownPending;
    }

    /**
     * @param Promise $runnableAcceptor
     * @return void
     */
    public function run(Promise $runnableAcceptor): void
    {
        $self = &$this;

        Loop::defer(static function () use (&$self, $runnableAcceptor) {

            yield $runnableAcceptor;

            $self->isShutdownPending = true;

            $self->getSessionManager()->shutdown();
            $self->getNetworkManager()->shutdown();

        });

        Loop::defer(static function () use (&$self) {

            while (!$self->isShutdownPending()) {

                yield $self->getSessionManager()->handle();

                yield new Delayed(100);

            }

        });

        Loop::defer(static function () use (&$self) {

            while (!$self->isShutdownPending()) {

                yield $self->getNetworkManager()->handle();

                yield new Delayed(100);

            }

        });
    }
}