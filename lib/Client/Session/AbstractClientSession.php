<?php


namespace Konfigurator\Network\Client\Session;


use Konfigurator\Network\Client\ClientNetworkManagerInterface;
use Konfigurator\Network\NetworkManagerInterface;
use Konfigurator\Network\Session\AbstractSession;
use Konfigurator\Network\Session\SessionManagerInterface;

abstract class AbstractClientSession extends AbstractSession implements ClientSessionInterface
{
    /**
     * AbstractClientSession constructor.
     * @param ClientSessionManagerInterface $sessionManager
     */
    public function __construct(ClientSessionManagerInterface $sessionManager)
    {
        parent::__construct($sessionManager);
    }

    /**
     * @return ClientNetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return parent::getNetworkManager();
    }

    /**
     * @return ClientSessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return parent::getSessionManager();
    }
}