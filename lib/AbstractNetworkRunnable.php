<?php


namespace Konfigurator\Network;


use Amp\Delayed;
use Konfigurator\Common\AbstractLoopRunnable;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Session\SessionManagerInterface;
use function Amp\asyncCall;
use function Amp\Promise\all;

abstract class AbstractNetworkRunnable extends AbstractLoopRunnable implements ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var NetworkManagerInterface */
    private NetworkManagerInterface $networkManager;

    /** @var SessionManagerInterface */
    private SessionManagerInterface $sessionManager;

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


    /**
     * AbstractNetworkRunnable constructor.
     */
    public function __construct()
    {
        $this->networkManager = $this->createNetworkManager();
        $this->sessionManager = $this->createSessionManager($this->networkManager);
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
     * @return void
     */
    public function shutdown(): void
    {
        parent::shutdown();

        $this->getSessionManager()->shutdown();
        $this->getNetworkManager()->shutdown();
    }

    /**
     * @return void
     */
    protected function _run(): void
    {
        asyncCall(static function (self &$self) {

            try {

                yield all([
                    $self->getSessionManager()->handle(),
                    $self->getNetworkManager()->handle(),
                ]);

                yield new Delayed(0);

            } catch (\Throwable $exception) {

                $self->exceptionLoopHandler($exception);

            }

            yield new Delayed(0);

            $self->shutdown();

        }, $this);
    }
}