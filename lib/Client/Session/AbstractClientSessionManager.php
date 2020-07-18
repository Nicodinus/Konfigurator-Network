<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Delayed;
use Amp\Promise;
use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\Client\NetworkManager\ConnectionEventEnum;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packets\PacketHandlerInterface;
use Konfigurator\Network\Packets\PacketInterface;
use Konfigurator\Network\Session\AbstractSessionManager;
use function Amp\asyncCall;
use function Amp\call;

abstract class AbstractClientSessionManager extends AbstractSessionManager implements ClientSessionManagerInterface
{
    /** @var ClientSessionInterface|null */
    private ?ClientSessionInterface $session;

    /** @var PacketHandlerInterface */
    private PacketHandlerInterface $packetHandler;

    /**
     * AbstractClientSession constructor.
     * @param ClientNetworkManagerInterface $networkManager
     */
    public function __construct(ClientNetworkManagerInterface $networkManager)
    {
        parent::__construct($networkManager);

        $this->session = null;

        $this->packetHandler = $this->createPacketHandler();
    }

    /**
     * @return Promise
     */
    public function handle(): Promise
    {
        return call(static function (self $self) {

            while (!$self->isShutdownPending()) {

                /** @var ConnectionEventEnum|null $event */
                $event = yield $self->getNetworkManager()->awaitEvent();
                if (!$event) {
                    yield new Delayed(0);
                    continue;
                }

                $self->getLogger()->debug("Handle event: {$event->getValue()}", [
                    'event' => $event,
                ]);

                asyncCall(static function (self $self, ConnectionEventEnum $event) {

                    try {

                        switch ($event->getValue())
                        {
                            case ConnectionEventEnum::CONNECTED()->getValue():
                                //$self->getClientSession()->onConnected();
                                $self->session = $self->createClientSession();
                                break;
                            case ConnectionEventEnum::DISCONNECTED()->getValue():
                                //$self->getClientSession()->onDisconnected();
                                $self->removeClientSession();
                                break;
                            case ConnectionEventEnum::PACKET_RECEIVED()->getValue():
                                //$self->getClientSession()->handlePacket($event->getEventData());
                                $packet = $self->getPacketHandler()
                                    ->handleRemotePacket($self->getClientSession(), $event->getEventData());
                                $self->getClientSession()->handle($packet);
                                break;
                        }

                    } catch (\Throwable $e) {

                        $self->getLogger()->error("Handle exception", [
                            'exception' => $e,
                        ]);
                        $self->disconnect();

                    }

                }, $self, $event);

                yield new Delayed(0);

            }

        }, $this);
    }

    /**
     * @return PacketHandlerInterface
     */
    protected abstract function createPacketHandler(): PacketHandlerInterface;

    /**
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return PacketHandlerInterface
     */
    public function getPacketHandler(): PacketHandlerInterface
    {
        return $this->packetHandler;
    }

    /**
     * @return ClientSessionInterface
     */
    protected abstract function createClientSession(): ClientSessionInterface;

    /**
     * @return static
     */
    protected function removeClientSession(): self
    {
        unset($this->session);
        $this->session = null;
    }

    /**
     * @return ClientSessionInterface
     */
    public function getClientSession(): ClientSessionInterface
    {
        return $this->session;
    }

    /**
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(PacketInterface $packet): Promise
    {
        $packet = $this->getPacketHandler()->handleLocalPacket($packet);

        return $this->getNetworkManager()->sendPacket($packet);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->session) {
            $this->removeClientSession();
        }

        $this->getNetworkManager()->disconnect();
    }
}