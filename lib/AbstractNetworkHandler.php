<?php


namespace Konfigurator\Network;


use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Common\Traits\GracefulShutdownTrait;
use function Amp\asyncCall;
use function Amp\call;

abstract class AbstractNetworkHandler implements NetworkHandlerInterface, ClassHasLogger
{
    use ClassHasLoggerTrait, GracefulShutdownTrait;

    /** @var NetworkHandlerState */
    private NetworkHandlerState $state;

    /** @var NetworkEventDispatcher */
    private NetworkEventDispatcher $eventDispatcher;

    /** @var Deferred|null */
    private ?Deferred $stateChangedAcceptor;


    /**
     * AbstractNetworkHandler constructor.
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(NetworkEventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->state = NetworkHandlerState::STOPPED();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->shutdown();
    }

    /**
     * @return NetworkHandlerState
     */
    public function getState(): NetworkHandlerState
    {
        return $this->state;
    }

    /**
     * @return Promise<void>
     */
    public function handle(): Promise
    {
        return call(static function (self &$self) {

            while (!$self->getState()->equals(NetworkHandlerState::RUNNING())) {
                $self->stateChangedAcceptor = new Deferred();
                yield $self->stateChangedAcceptor->promise();
                $self->stateChangedAcceptor = null;
            }

            /*
            if (!$self->getState()->equals(NetworkHandlerState::RUNNING())) {
                return new Failure(new \LogicException("Invalid network handler state!"));
            }
            */

            try {

                yield $self->_handle();

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::handle exception!", [
                    'exception' => $e,
                ]);

                $self->shutdown();

                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @param NetworkHandlerState $state
     * @return static
     */
    protected function setState(NetworkHandlerState $state): self
    {
        $this->state = $state;

        if (!empty($this->stateChangedAcceptor)) {
            $this->stateChangedAcceptor->resolve();
        }

        return $this;
    }

    /**
     * @return NetworkEventDispatcher
     */
    protected function getEventDispatcher(): NetworkEventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return Promise<void>
     */
    protected abstract function _handle(): Promise;
}