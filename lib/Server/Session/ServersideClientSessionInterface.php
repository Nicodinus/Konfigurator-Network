<?php


namespace Konfigurator\Network\Server\Session;


use Amp\Socket\SocketAddress;
use Konfigurator\Network\Session\SessionInterface;


interface ServersideClientSessionInterface extends SessionInterface
{
    /**
     * @return SocketAddress
     */
    public function getRemoteAddress(): SocketAddress;
}