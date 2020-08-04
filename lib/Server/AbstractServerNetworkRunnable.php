<?php


namespace Konfigurator\Network\Server;


use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\AbstractNetworkRunnable;
use Konfigurator\Network\NetworkHandlerInterface;
use Konfigurator\Network\NetworkHandlerState;
use function Amp\call;

abstract class AbstractServerNetworkRunnable extends AbstractNetworkRunnable
{
    /** @var SocketAddress */
    protected SocketAddress $listenAddress;


    /**
     * AbstractServerRunnable constructor.
     * @param SocketAddress $listenAddress
     */
    public function __construct(SocketAddress $listenAddress)
    {
        parent::__construct();

        $this->listenAddress = $listenAddress;
    }

    /**
     * @return ServerNetworkHandlerInterface
     */
    public function getNetworkHandler(): NetworkHandlerInterface
    {
        return parent::getNetworkHandler();
    }

    /**
     * @return ServerNetworkHandlerInterface
     */
    protected function createNetworkHandler(): NetworkHandlerInterface
    {
        return new ServerNetworkHandler($this->getEventDispatcher());
    }

    /**
     * @return Promise<void>
     */
    public function handle(): Promise
    {
        if (!$this->getNetworkHandler()->getState()->equals(NetworkHandlerState::RUNNING())) {

            return call(static function (self &$self) {

                try {

                    yield $self->getNetworkHandler()->listen($self->listenAddress);

                } catch (\Throwable $exception) {

                    $self->handleException($exception);
                    //ignore
                }

            }, $this);

        }

        return parent::handle();
    }
}