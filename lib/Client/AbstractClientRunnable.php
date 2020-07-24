<?php


namespace Konfigurator\Network\Client;


use Amp\Delayed;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\AbstractNetworkRunnable;
use Konfigurator\Network\Client\NetworkManager\ConnectionStateEnum;
use Konfigurator\Network\Client\Session\ClientSessionManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Session\SessionManagerInterface;
use function Amp\asyncCall;

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
     * @return void
     */
    public function _run(): void
    {
        asyncCall(static function (self &$self) {

            while (!$self->isShutdownPending()) {

                while (!$self->getNetworkManager()->getState()->equals(ConnectionStateEnum::CONNECTED())) {

                    try {

                        yield $self->getNetworkManager()->connect($self->address, $self->timeout);

                    } catch (\Throwable $exception) {

                        $self->exceptionLoopHandler($exception);
                        //ignore

                    }

                }

                yield new Delayed(1000);

            }

        }, $this);

        parent::_run();
    }
}