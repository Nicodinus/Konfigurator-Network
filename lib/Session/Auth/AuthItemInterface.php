<?php


namespace Konfigurator\Network\Session\Auth;


use Konfigurator\Network\Session\SessionInterface;

interface AuthItemInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @return array
     */
    public function getCredentials(): array;

    /**
     * @param SessionInterface $session
     * @return static
     */
    public function withSession($session);

    /**
     * @return SessionInterface|null
     */
    public function getSession(): ?SessionInterface;

    /**
     * @return static
     */
    public function clearSession();

    /**
     * @return array
     */
    public function toArray(): array;
}