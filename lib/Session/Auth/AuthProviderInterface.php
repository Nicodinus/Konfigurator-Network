<?php


namespace Konfigurator\Network\Session\Auth;


interface AuthProviderInterface
{
    /**
     * @param array $credentials
     * @return AuthItemInterface|null
     */
    public function retrieveByCredentials(array $credentials): ?AuthItemInterface;

    /**
     * @param int|string $id
     * @return AuthItemInterface|null
     */
    public function retrieveById($id): ?AuthItemInterface;
}