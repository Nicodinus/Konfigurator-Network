<?php


namespace Konfigurator\Network\Client;


use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Network\AbstractNetworkRunnable;
use Konfigurator\Network\NetworkHandlerInterface;
use Konfigurator\Network\NetworkHandlerState;
use function Amp\call;

abstract class AbstractClientNetworkRunnable extends AbstractNetworkRunnable
{
    /** @var SocketAddress */
    protected SocketAddress $address;

    /** @var int */
    protected int $timeout;


    /**
     * AbstractClientRunnable constructor.
     * @param SocketAddress $address
     * @param int $timeout
     */
    public function __construct(SocketAddress $address, int $timeout = 1000)
    {
        parent::__construct();

        $this->address = $address;
        $this->timeout = $timeout;
    }

    /**
     * @return ClientNetworkHandlerInterface
     */
    public function getNetworkHandler(): NetworkHandlerInterface
    {
        return parent::getNetworkHandler();
    }

    /**
     * @return ClientNetworkHandlerInterface
     */
    protected function createNetworkHandler(): NetworkHandlerInterface
    {
        return new ClientNetworkHandler($this->getEventDispatcher());
    }

    /**
     * @return Promise<void>
     */
    public function handle(): Promise
    {
        if (!$this->getNetworkHandler()->getState()->equals(NetworkHandlerState::RUNNING())) {

            return call(static function (self &$self) {

                try {

                    yield $self->getNetworkHandler()->connect($self->address, (new ConnectContext())
                        ->withTcpNoDelay()
                        ->withConnectTimeout($self->timeout)
                    );

                } catch (\Throwable $exception) {

                    $self->exceptionHandler($exception);
                    //ignore
                }

            }, $this);

        }

        return parent::handle();
    }
}