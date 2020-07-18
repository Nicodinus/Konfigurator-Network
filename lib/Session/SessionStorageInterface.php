<?php


namespace Konfigurator\Network\Session;


interface SessionStorageInterface extends \ArrayAccess, \Countable
{
    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;

    /**
     * @param $key
     * @param mixed $value
     * @return static
     */
    public function store($key, $value): self;

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param $key
     * @return static
     */
    public function remove($key): self;
}