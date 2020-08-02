<?php


namespace Konfigurator\Network\Session;


use Amp\Socket\SocketAddress;
use Konfigurator\Network\Client\ClientNetworkHandlerInterface;

interface SessionManagerInterface
{
    /**
     * @param ClientNetworkHandlerInterface $networkHandler
     * @return SessionInterface
     */
    public function createSession(ClientNetworkHandlerInterface $networkHandler): SessionInterface;

    /**
     * @param SocketAddress $address
     * @return SessionInterface|null
     */
    public function getSession(SocketAddress $address): ?SessionInterface;

    /**
     * @return SessionInterface[]
     */
    public function getSessions(): array;
}