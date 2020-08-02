<?php


namespace Konfigurator\Network\Session;


use Amp\Socket\SocketAddress;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Client\ClientNetworkHandlerEvent;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
use Konfigurator\Network\NetworkEventDispatcher;

abstract class AbstractSessionManager implements SessionManagerInterface, ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var SessionInterface[] */
    private array $sessions;

    /** @var NetworkEventDispatcher */
    private NetworkEventDispatcher $eventDispatcher;


    /**
     * AbstractSessionManager constructor.
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(NetworkEventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        $this->sessions = [];

        $self = &$this;

        $this->getEventDispatcher()
            ->addListener(ClientNetworkHandlerEvent::CONNECTED(), function (ClientNetworkHandlerEvent $event) use (&$self) {
                $self->createSession($event->getNetworkHandler());
            })
            ->addListener(ClientNetworkHandlerEvent::DISCONNECTED(), function (ClientNetworkHandlerEvent $event) use (&$self) {
                $self->removeSession($event->getNetworkHandler()->getAddress());
            })
            ->addListener(ClientNetworkHandlerEvent::PACKET_RECEIVED(), function (ClientNetworkHandlerEvent $event) use (&$self) {
                yield $self->getSession($event->getNetworkHandler()->getAddress())->handlePacket($event->getEventData());
            })
        ;
    }

    /**
     * @param ClientNetworkHandlerInterface $networkHandler
     * @return SessionInterface
     */
    public function createSession(ClientNetworkHandlerInterface $networkHandler): SessionInterface
    {
        $sessionInstance = $this->createSessionInstance($networkHandler);

        $this->sessions[$networkHandler->getAddress()->toString()] = $sessionInstance;

        return $sessionInstance;
    }

    /**
     * @param SocketAddress $address
     * @return SessionInterface|null
     */
    public function getSession(SocketAddress $address): ?SessionInterface
    {
        return $this->sessions[$address->toString()] ?? null;
    }

    /**
     * @return SessionInterface[]
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }

    /**
     * @param SocketAddress $address
     * @return void
     */
    protected function removeSession(SocketAddress $address): void
    {
        unset($this->sessions[$address->toString()]);
    }

    /**
     * @return NetworkEventDispatcher
     */
    protected function getEventDispatcher(): NetworkEventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @param ClientNetworkHandlerInterface $networkHandler
     * @return SessionInterface
     */
    protected abstract function createSessionInstance(ClientNetworkHandlerInterface $networkHandler): SessionInterface;
}