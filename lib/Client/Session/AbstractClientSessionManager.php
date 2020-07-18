<?php


namespace Konfigurator\Network\Client\Session;


use Amp\Delayed;
use Amp\Promise;
use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\Client\NetworkManager\ConnectionEventEnum;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Session\AbstractSessionManager;
use function Amp\asyncCall;
use function Amp\call;

abstract class AbstractClientSessionManager extends AbstractSessionManager implements ClientSessionManagerInterface
{
    /** @var ClientSessionInterface|null */
    protected ?ClientSessionInterface $session;

    /**
     * AbstractClientSession constructor.
     * @param ClientNetworkManagerInterface $networkManager
     */
    public function __construct(ClientNetworkManagerInterface $networkManager)
    {
        parent::__construct($networkManager);

        $this->session = null;
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
                                $self->getClientSession()->onConnected();
                                break;
                            case ConnectionEventEnum::DISCONNECTED()->getValue():
                                $self->getClientSession()->onDisconnected();
                                break;
                            case ConnectionEventEnum::PACKET_RECEIVED()->getValue():
                                $self->getClientSession()->handlePacket($event->getEventData());
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
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return ClientSessionInterface
     */
    protected abstract function createClientSession(): ClientSessionInterface;

    /**
     * @return ClientSessionInterface
     */
    public function getClientSession(): ClientSessionInterface
    {
        if (!$this->session) {
            $this->session = $this->createClientSession();
        }

        return $this->session;
    }

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>
     */
    public function sendPacket($packet): Promise
    {
        return $this->getNetworkManager()->sendPacket($packet);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        $this->getNetworkManager()->disconnect();
    }
}