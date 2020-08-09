<?php


namespace Konfigurator\Network\Session;


use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\Client\ClientNetworkHandlerEvent;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerState;
use Konfigurator\Network\Packet\InputPacketInterface;
use Konfigurator\Network\Packet\OutputPacketInterface;
use Konfigurator\Network\Packet\PacketHandlerInterface;
use Konfigurator\Network\Session\Auth\AuthGuardInterface;
use function Amp\call;

abstract class AbstractSession implements SessionInterface, ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var ClientNetworkHandlerInterface */
    private ClientNetworkHandlerInterface $networkHandler;

    /** @var PacketHandlerInterface */
    private PacketHandlerInterface $packetHandler;

    /** @var SessionStorageInterface */
    private SessionStorageInterface $storage;

    /** @var AuthGuardInterface */
    private AuthGuardInterface $authGuard;

    /** @var NetworkEventDispatcher */
    private NetworkEventDispatcher $eventDispatcher;

    /** @var array<string, Deferred[]> */
    private array $packetListeners = [];

    /** @var Deferred[] */
    private array $anyPacketListeners = [];


    /**
     * AbstractClientSession constructor.
     * @param ClientNetworkHandlerInterface $networkHandler
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(ClientNetworkHandlerInterface $networkHandler, NetworkEventDispatcher $eventDispatcher)
    {
        $this->networkHandler = $networkHandler;
        $this->eventDispatcher = $eventDispatcher;

        $this->storage = $this->createStorage();
        $this->authGuard = $this->createAuthGuard();
        $this->packetHandler = $this->createPacketHandler();

        $this->getAuthGuard()->restoreAuth();

        $self = &$this;

        $this->eventDispatcher
            ->addListener(ClientNetworkHandlerEvent::DISCONNECTED(), function () use (&$self) {
                foreach ($self->packetListeners as $arr) {
                    /** @var Deferred $defer */
                    foreach ($arr as $defer) {
                        $defer->resolve();
                    }
                }
                $self->packetListeners = [];
                foreach ($self->anyPacketListeners as $defer) {
                    $defer->resolve();
                }
                $self->anyPacketListeners = [];
            })
        ;
    }

    /**
     * @return bool
     */
    public function isAlive(): bool
    {
        return $this->getNetworkHandler()->getState()->equals(NetworkHandlerState::RUNNING());
    }

    /**
     * @return AuthGuardInterface
     */
    public function getAuthGuard(): AuthGuardInterface
    {
        return $this->authGuard;
    }

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface
    {
        return $this->storage;
    }

    /**
     * @return SocketAddress
     */
    public function getAddress(): SocketAddress
    {
        return $this->networkHandler->getAddress();
    }

    /**
     * @param OutputPacketInterface $outputPacket
     *
     * @return Promise<void>|Failure<\Throwable>
     */
    public function sendPacket(OutputPacketInterface $outputPacket): Promise
    {
        return call(static function (self &$self, OutputPacketInterface $outputPacket) {

            try {

                $self->getLogger()->debug("SEND packet: " . get_class($outputPacket));

                return yield $self->getNetworkHandler()->sendPacket(
                    yield $self->getPacketHandler()->transform($outputPacket)
                );

            } catch (\Throwable $e) {

                $self->getLogger()->warning("Sending packet error!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

        }, $this, $outputPacket);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        $this->getNetworkHandler()->disconnect();
    }

    /**
     * @param mixed $inputPacketId
     * @return Promise<InputPacketInterface|null>|Failure<\Throwable>
     */
    public function awaitPacket($inputPacketId): Promise
    {
        if (!$this->getPacketHandler()->getPacketRepository()->findInputPacket($inputPacketId)) {
            return new Failure(new \LogicException("Invalid packet id {$inputPacketId}!"));
        }

        if (!isset($this->packetListeners[$inputPacketId])) {
            $this->packetListeners[$inputPacketId] = [];
        }

        $defer = new Deferred();

        $this->packetListeners[$inputPacketId][] = $defer;

        return $defer->promise();
    }

    /**
     * @return Promise<InputPacketInterface|null>
     */
    public function awaitAnyPacket(): Promise
    {
        $defer = new Deferred();

        $this->anyPacketListeners[] = $defer;

        return $defer->promise();
    }

    /**
     * @param InputPacketInterface $inputPacket
     * @return Promise<void>
     */
    public function handlePacket(InputPacketInterface $inputPacket): Promise
    {
        return call(static function (self &$self, InputPacketInterface $inputPacket) {

            try {

                $packet = yield $self->getPacketHandler()->handlePacket($self, $inputPacket);

                $self->getLogger()->debug("RECV packet: " . get_class($inputPacket));

                $self->notifyPacketListeners($inputPacket);

                yield $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_HANDLED($self->getNetworkHandler(), $packet));

                $response = yield $self->handleReceivedPacket($inputPacket);
                if (!empty($response)) {
                    yield $self->sendPacket($response);
                }

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this, $inputPacket);
    }

    /**
     * @param InputPacketInterface $inputPacket
     * @return void
     */
    protected function notifyPacketListeners(InputPacketInterface $inputPacket): void
    {
        if (isset($this->packetListeners[$inputPacket::getId()])) {

            while ($listener = array_shift($this->packetListeners[$inputPacket::getId()])) {
                $listener->resolve($inputPacket);
            }

        }

        while ($listener = array_shift($this->anyPacketListeners)) {
            $listener->resolve($inputPacket);
        }
    }

    /**
     * @return NetworkEventDispatcher
     */
    protected function getEventDispatcher(): NetworkEventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return SessionStorageInterface
     */
    protected function createStorage(): SessionStorageInterface
    {
        return new SessionStorage($this);
    }

    /**
     * @return PacketHandlerInterface
     */
    protected function getPacketHandler(): PacketHandlerInterface
    {
        return $this->packetHandler;
    }

    /**
     * @return ClientNetworkHandlerInterface
     */
    protected function getNetworkHandler(): ClientNetworkHandlerInterface
    {
        return $this->networkHandler;
    }

    /**
     * @param mixed $outputPacketId
     * @param mixed ...$args
     *
     * @return OutputPacketInterface
     *
     * @throws \Throwable
     */
    public function createPacket($outputPacketId, ...$args): OutputPacketInterface
    {
        if (!$this->getPacketHandler()->getPacketRepository()->isPacketExist($outputPacketId)) {
            throw new \LogicException("Invalid packet id {$outputPacketId}!");
        }

        return $this->getPacketHandler()->getPacketRepository()->createPacket($this, $outputPacketId, ...$args);
    }

    /**
     * @return AuthGuardInterface
     */
    protected abstract function createAuthGuard(): AuthGuardInterface;

    /**
     * @return PacketHandlerInterface
     */
    protected abstract function createPacketHandler(): PacketHandlerInterface;

    /**
     * @param InputPacketInterface $inputPacket
     * @return Promise<OutputPacketInterface|null>
     */
    protected abstract function handleReceivedPacket(InputPacketInterface $inputPacket): Promise;
}