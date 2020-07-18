<?php


namespace Konfigurator\Network\Client;


use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\AbstractNetworkRunnable;
use Konfigurator\Network\Client\NetworkManager\ConnectionStateEnum;
use Konfigurator\Network\Client\Session\ClientSessionManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractClientRunnable extends AbstractNetworkRunnable
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
     * @return ClientSessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return parent::getSessionManager();
    }

    /**
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return ClientNetworkManagerInterface
     */
    protected function createNetworkManager(): NetworkManagerInterface
    {
        return new ClientNetworkManager();
    }

    /**
     * @param Promise $runnableAcceptor
     * @return void
     */
    public function run(Promise $runnableAcceptor): void
    {
        $self = &$this;

        Loop::defer(static function () use (&$self) {

            while (!$self->isShutdownPending()) {

                while (!$self->getNetworkManager()->getState()->equals(ConnectionStateEnum::CONNECTED())) {

                    try {

                        yield $self->getNetworkManager()->connect($self->address, $self->timeout);

                    } catch (\Throwable $e) {

                        $self->getLogger()->error("Run exception", [
                            'exception' => $e,
                        ]);
                        //ignore

                    }

                }

                yield new Delayed(1000);

            }

        });

        parent::run($runnableAcceptor);
    }
}