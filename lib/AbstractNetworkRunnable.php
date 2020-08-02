<?php


namespace Konfigurator\Network;


use Amp\Promise;
use Konfigurator\Common\AbstractLoopRunnable;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractNetworkRunnable extends AbstractLoopRunnable implements ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var NetworkHandlerInterface */
    private NetworkHandlerInterface $networkHandler;

    /** @var SessionManagerInterface */
    private SessionManagerInterface $sessionManager;

    /** @var NetworkEventDispatcher */
    private NetworkEventDispatcher $eventDispatcher;


    /**
     * AbstractNetworkRunnable constructor.
     * @param NetworkEventDispatcher|null $eventDispatcher
     */
    public function __construct(?NetworkEventDispatcher $eventDispatcher = null)
    {
        parent::__construct();

        $this->eventDispatcher = $eventDispatcher ?? new NetworkEventDispatcher();

        $this->networkHandler = $this->createNetworkHandler();
        $this->sessionManager = $this->createSessionManager();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * @return NetworkEventDispatcher
     */
    public function getEventDispatcher(): NetworkEventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return SessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return $this->sessionManager;
    }

    /**
     * @return NetworkHandlerInterface
     */
    public function getNetworkHandler(): NetworkHandlerInterface
    {
        return $this->networkHandler;
    }

    /**
     * @return Promise<void>
     */
    public function handle(): Promise
    {
        return $this->getNetworkHandler()->handle();
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        parent::shutdown();

        $this->getNetworkHandler()->shutdown();
    }

    /**
     * @param \Throwable $exception
     * @param string|null $message
     */
    protected function exceptionHandler(\Throwable $exception, ?string $message = null): void
    {
        $this->getLogger()->error($message ?? "Exception handle", [
            'exception' => $exception,
        ]);
    }

    /**
     * @return SessionManagerInterface
     */
    protected abstract function createSessionManager(): SessionManagerInterface;

    /**
     * @return NetworkHandlerInterface
     */
    protected abstract function createNetworkHandler(): NetworkHandlerInterface;
}