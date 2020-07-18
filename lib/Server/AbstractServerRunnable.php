<?php


namespace Konfigurator\Network\Server;


use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Konfigurator\Network\AbstractNetworkRunnable;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Server\NetworkManager\ServerStateEnum;
use Konfigurator\Network\Server\Session\ServerSessionManagerInterface;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractServerRunnable extends AbstractNetworkRunnable
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
     * @return ServerSessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return parent::getSessionManager();
    }

    /**
     * @return ServerNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return ServerNetworkManagerInterface
     */
    protected function createNetworkManager(): NetworkManagerInterface
    {
        return new ServerNetworkManager();
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

                while (!$self->getNetworkManager()->getState()->equals(ServerStateEnum::LISTEN())) {

                    try {

                        yield $self->getNetworkManager()->listen($self->listenAddress);

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