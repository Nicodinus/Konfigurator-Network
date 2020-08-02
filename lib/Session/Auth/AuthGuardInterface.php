<?php


namespace Konfigurator\Network\Session\Auth;


use Konfigurator\Network\Session\SessionInterface;

interface AuthGuardInterface
{
    /**
     * @return void
     */
    public function restoreAuth(): void;

    /**
     * @return bool
     */
    public function isAuthorized(): bool;

    /**
     * @return void
     */
    public function logout(): void;

    /**
     * @param AuthItemInterface $authItem
     * @return void
     */
    public function authorize(AuthItemInterface $authItem): void;

    /**
     * @return AuthItemInterface|null
     */
    public function getAuthItem(): ?AuthItemInterface;

    /**
     * @return AuthProviderInterface
     */
    public function getProvider(): AuthProviderInterface;

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;
}