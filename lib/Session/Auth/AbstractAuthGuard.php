<?php


namespace Konfigurator\Network\Session\Auth;


use Konfigurator\Network\Session\SessionInterface;

abstract class AbstractAuthGuard implements AuthGuardInterface
{
    /** @var SessionInterface */
    private SessionInterface $session;

    /** @var AuthItemInterface|null */
    private ?AuthItemInterface $authItem;


    /**
     * AbstractAuthGuard constructor.
     * @param SessionInterface $session
     */
    public function __construct($session)
    {
        $this->session = $session;
        $this->authItem = null;
    }

    /**
     * @return void
     */
    public function restoreAuth(): void
    {
        if (!$this->getSession()->getStorage()->has(AuthItemInterface::class)) {
            return;
        }

        $this->authorize($this->getSession()->getStorage()->get(AuthItemInterface::class));
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return !empty($this->getAuthItem());
    }

    /**
     * @param AuthItemInterface $authItem
     * @return void
     */
    public function authorize(AuthItemInterface $authItem): void
    {
        $this->logout();

        $this->authItem = $authItem->withSession($this->getSession());

        $this->getSession()->getStorage()->store(AuthItemInterface::class, $this->authItem);
    }

    /**
     * @return void
     */
    public function logout(): void
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->getAuthItem()->clearSession();

        $this->authItem = null;

        $this->getSession()->getStorage()->remove(AuthItemInterface::class);
    }

    /**
     * @return AuthItemInterface|null
     */
    public function getAuthItem(): ?AuthItemInterface
    {
        return $this->authItem;
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->authItem = null;
    }
}