<?php


namespace Konfigurator\Network\Server\Session;


use Amp\Delayed;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packet\PacketInterface;
use Konfigurator\Network\Server\NetworkManager\ClientEventEnum;
use Konfigurator\Network\Server\NetworkManager\ServerEventEnum;
use Konfigurator\Network\Server\ServerNetworkManagerInterface;
use Konfigurator\Network\Session\AbstractSessionManager;
use function Amp\asyncCall;
use function Amp\call;

abstract class AbstractServerSessionManager extends AbstractSessionManager implements ServerSessionManagerInterface
{
    /** @var ServersideClientSessionInterface[] */
    private array $sessions;


    /**
     * AbstractServerSessionManager constructor.
     * @param ServerNetworkManagerInterface $networkManager
     */
    public function __construct(ServerNetworkManagerInterface $networkManager)
    {
        parent::__construct($networkManager);

        $this->sessions = [];
    }

    /**
     * @param SocketAddress $peer
     * @return ServersideClientSessionInterface
     */
    protected function registerClient(SocketAddress $peer): ServersideClientSessionInterface
    {
        return $this->sessions[$peer->toString()] = $this->createClientSession($peer);
    }

    /**
     * @param SocketAddress $peer
     * @return void
     */
    protected function removeClient(SocketAddress $peer): void
    {
        unset($this->sessions[$peer->toString()]);
    }

    /**
     * @param SocketAddress $peer
     * @return ServersideClientSessionInterface|null
     */
    public function getClientSession(SocketAddress $peer): ?ServersideClientSessionInterface
    {
        return $this->sessions[$peer->toString()] ?? null;
    }

    /**
     * @return Promise
     */
    public function handle(): Promise
    {
        return call(static function (self &$self) {

            while (!$self->isShutdownPending()) {

                /** @var ServerEventEnum $event */
                $event = yield $self->getNetworkManager()->awaitEvent();
                if (!$event) {
                    yield new Delayed(0);
                    continue;
                }

                if (!($event instanceof ClientEventEnum) || !$event->getRemoteAddress()) {

                    $self->getLogger()->debug("Event skipped: {$event->getValue()}", [
                        'event' => $event,
                    ]);

                    yield new Delayed(0);
                    continue;

                }

                $self->getLogger()->debug("Handle event: {$event->getValue()}", [
                    'event' => $event,
                    'address' => $event->getRemoteAddress(),
                ]);

                asyncCall(static function (self &$self, ClientEventEnum $event) {

                    $peer = $event->getRemoteAddress();

                    try {

                        switch ($event->getValue())
                        {
                            case ClientEventEnum::CONNECTED()->getValue():
                                //$self->getLogger()->debug("A new client {$peer} connected!");
                                //$self->registerClient($peer)->onConnected();
                                $self->registerClient($peer);
                                break;
                            case ClientEventEnum::DISCONNECTED()->getValue():
                                //$self->getLogger()->debug("Client {$peer} disconnected!");
                                //$self->getClientSession($peer)->onDisconnected();
                                $self->removeClient($peer);
                                break;
                            case ClientEventEnum::PACKET_RECEIVED()->getValue():
                                //$self->getLogger()->debug("Recv packet from {$peer} length " . strlen($packet));
                                $packet = $self->getPacketHandler()
                                    ->handlePacket($self->getClientSession($peer), $event->getEventData());
                                $self->getClientSession($peer)->handle($packet);
                                //$self->getClientSession($peer)->handlePacket($event->getEventData());
                                break;
                        }

                    } catch (\Throwable $e) {

                        $self->getLogger()->error("Handle exception", [
                            'exception' => $e,
                        ]);
                        $self->disconnect($peer);

                    }

                }, $self, $event);

                yield new Delayed(0);

            }

        }, $this);
    }

    /**
     * @return ServerNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @param SocketAddress $address
     * @param PacketInterface $packet
     * @return Promise<void>
     */
    public function sendPacket(SocketAddress $address, PacketInterface $packet): Promise
    {
        $packet = $this->getPacketHandler()->preparePacket($packet);

        return $this->getNetworkManager()->sendPacket($address, $packet);
    }

    /**
     * @param SocketAddress $address
     * @return void
     */
    public function disconnect(SocketAddress $address): void
    {
        if ($this->getClientSession($address)) {
            $this->removeClient($address);
        }

        $this->getNetworkManager()->disconnect($address);
    }

    /**
     * @param SocketAddress $address
     * @return ServersideClientSessionInterface
     */
    protected abstract function createClientSession(SocketAddress $address): ServersideClientSessionInterface;
}