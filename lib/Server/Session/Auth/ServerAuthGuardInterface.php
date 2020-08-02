<?php


namespace Konfigurator\Network\Server\Session\Auth;


use Konfigurator\Network\Session\Auth\AuthGuardInterface;

interface ServerAuthGuardInterface extends AuthGuardInterface
{
    /**
     * @param array $credentials
     * @return bool
     */
    public function attemptAuthorize(array $credentials): bool;

    /**
     * @param int|string $id
     * @return bool
     */
    public function authorizeById($id): bool;
}