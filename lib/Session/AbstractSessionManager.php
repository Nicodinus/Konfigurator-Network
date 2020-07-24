<?php


namespace Konfigurator\Network\Session;


use Konfigurator\Common\AbstractAsyncHandler;
use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Packet\PacketHandlerInterface;

abstract class AbstractSessionManager extends AbstractAsyncHandler implements SessionManagerInterface, ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var NetworkManagerInterface */
    private NetworkManagerInterface $networkManager;

    /** @var PacketHandlerInterface */
    private PacketHandlerInterface $packetHandler;


    /**
     * AbstractSessionManager constructor.
     * @param NetworkManagerInterface $networkManager
     */
    public function __construct(NetworkManagerInterface $networkManager)
    {
        $this->networkManager = $networkManager;
        $this->packetHandler = $this->createPacketHandler();
    }

    /**
     * @param \Throwable $exception
     * @param string|null $message
     */
    protected function exceptionLoopHandler(\Throwable $exception, ?string $message = null): void
    {
        $this->getLogger()->error($message ?? __CLASS__ . " throws an exception!", [
            'exception' => $exception,
        ]);
    }

    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return $this->networkManager;
    }

    /**
     * @return PacketHandlerInterface
     */
    public function getPacketHandler(): PacketHandlerInterface
    {
        return $this->packetHandler;
    }

    /**
     * @return PacketHandlerInterface
     */
    protected abstract function createPacketHandler(): PacketHandlerInterface;
}