<?php


namespace Konfigurator\Network\Session;


class SessionStorage implements SessionStorageInterface
{
    /** @var SessionInterface */
    protected SessionInterface $session;

    /** @var array */
    protected array $data;


    /**
     * SessionStorage constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->data = [];
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * @param $key
     * @param mixed $value
     * @return static
     */
    public function store($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \LogicException("Can't find {$key} at the session store!");
        }

        return $this->data[$key];
    }

    /**
     * @param $key
     * @return static
     */
    public function remove($key)
    {
        if (!$this->has($key)) {
            throw new \LogicException("Can't find {$key} at the session store!");
        }

        unset($this->data[$key]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->store($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return sizeof($this->data);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->data = [];
    }
}