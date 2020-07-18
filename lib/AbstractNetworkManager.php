<?php


namespace Konfigurator\Network;


use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Konfigurator\Common\Enums\EventEnum;
use Konfigurator\Common\Enums\StateEnum;
use Konfigurator\Common\Exceptions\PendingShutdownError;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\asyncCall;

abstract class AbstractNetworkManager implements NetworkManagerInterface, ClassHasLogger
{
    /** @var StateEnum */
    private StateEnum $state;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var Deferred|null */
    private ?Deferred $eventAcceptor;

    /** @var bool */
    private bool $isShutdownPending;


    /**
     * AbstractNetworkManager constructor.
     */
    public function __construct()
    {
        $this->state = StateEnum::UNDEFINED();
        $this->logger = new NullLogger();
        $this->isShutdownPending = false;
        $this->eventAcceptor = null;
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

        $this->notifyEventAcceptor(null);
    }

    /**
     * @return StateEnum
     */
    public function getState(): StateEnum
    {
        return $this->state;
    }

    /**
     * @param StateEnum $state
     * @return static
     */
    protected function setState(StateEnum $state): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param EventEnum|null $event
     */
    protected function notifyEventAcceptor(?EventEnum $event = null): void
    {
        asyncCall(static function (self $self, $event) {

            yield new Delayed(0);

            if (!empty($self->eventAcceptor)) {

                $self->eventAcceptor->resolve($event);
                $self->eventAcceptor = null;

            }

            yield new Delayed(0);

        }, $this, $event);
    }

    /**
     * @return Promise<EventEnum|null>|Failure<PendingShutdownError>
     */
    public function awaitEvent(): Promise
    {
        if ($this->isShutdownPending()) {
            return new Failure(new PendingShutdownError());
        }

        if (empty($this->eventAcceptor)) {
            $this->eventAcceptor = new Deferred();
        }

        return $this->eventAcceptor->promise();
    }

    /**
     * @return bool
     */
    public function isShutdownPending(): bool
    {
        return $this->isShutdownPending;
    }
}