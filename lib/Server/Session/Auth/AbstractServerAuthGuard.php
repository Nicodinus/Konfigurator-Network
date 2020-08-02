<?php


namespace Konfigurator\Network\Server\Session\Auth;


use Konfigurator\Network\Session\Auth\AbstractAuthGuard;

abstract class AbstractServerAuthGuard extends AbstractAuthGuard implements ServerAuthGuardInterface
{
    /**
     * @param array $credentials
     * @return bool
     */
    public function attemptAuthorize(array $credentials): bool
    {
        $authItem = $this->getProvider()->retrieveByCredentials($credentials);
        if (empty($authItem)) {
            return false;
        }

        $this->authorize($authItem);

        return true;
    }

    /**
     * @param int|string $id
     * @return bool
     */
    public function authorizeById($id): bool
    {
        $authItem = $this->getProvider()->retrieveById($id);
        if (empty($authItem)) {
            return false;
        }

        $this->authorize($authItem);

        return true;
    }
}