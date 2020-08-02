<?php


namespace Konfigurator\Network;


use Amp\Promise;
use Amp\Success;
use Konfigurator\Common\Enums\EventEnum;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NetworkEventDispatcher
{
    const PRIORITY_MAX = PHP_INT_MAX;
    const PRIORITY_MIN = PHP_INT_MIN;

    /** @var EventDispatcherInterface */
    private EventDispatcherInterface $eventDispatcher;


    /**
     * NetworkEventDispatcher constructor.
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        /*
        foreach ($this->eventDispatcher->getListeners() as $eventName => $listener) {
            $this->eventDispatcher->removeListener($eventName, $listener);
        }
        */
    }

    /**
     * @param EventEnum $event
     * @param callable $callable
     * @param int $priority
     * @return static
     */
    public function addListener(EventEnum $event, callable $callable, int $priority = 0)
    {
        $this->eventDispatcher->addListener($this->transformEventName($event), $callable, $priority);
        return $this;
    }

    /**
     * @param EventEnum $event
     * @param callable $callable
     * @return static
     */
    public function removeListener(EventEnum $event, callable $callable)
    {
        $this->eventDispatcher->removeListener($this->transformEventName($event), $callable);
        return $this;
    }

    /**
     * @param EventEnum|null $event
     * @return array<EventEnum|string, callable>
     */
    public function getListeners(?EventEnum $event = null): array
    {
        return $this->eventDispatcher->getListeners(!empty($event) ? $event->getValue() : null);
    }

    /**
     * @param EventEnum|null $event
     * @return Promise<EventEnum>
     */
    public function dispatch(?EventEnum $event = null): Promise
    {
        $event = $event ?? EventEnum::EMPTY();

        $result = $this->eventDispatcher->dispatch($event, $this->transformEventName($event));
        if ($result instanceof Promise) {
            return $result;
        }

        return new Success($result);
    }

    /**
     * @param EventEnum $eventEnum
     * @return string
     */
    public function transformEventName(EventEnum $eventEnum): string
    {
        return basename(get_class($eventEnum)) . '::' . $eventEnum->getValue();
    }
}