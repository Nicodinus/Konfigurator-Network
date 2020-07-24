<?php


namespace Konfigurator\Network\Session;


use Konfigurator\Common\Interfaces\ClassHasLogger;
use Konfigurator\Common\Traits\ClassHasLoggerTrait;
use Konfigurator\Network\NetworkManagerInterface;
use Ramsey\Uuid\Uuid;

abstract class AbstractSession implements SessionInterface, ClassHasLogger
{
    use ClassHasLoggerTrait;

    /** @var string[] */
    private static array $aliveSessionIds = [];

    /** @var SessionManagerInterface */
    private SessionManagerInterface $sessionManager;

    /** @var SessionStorageInterface */
    private SessionStorageInterface $storage;

    /** @var string|float|int|bool|null */
    private $id;


    /**
     * AbstractClientSession constructor.
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->storage = $this->createStorage();

        $this->id = $this->_getId();
        static::$aliveSessionIds[] = $this->id;
    }

    /**
     * @return SessionStorageInterface
     */
    protected function createStorage(): SessionStorageInterface
    {
        return new SessionStorage($this);
    }

    /**
     * @return SessionStorageInterface
     */
    public function getStorage(): SessionStorageInterface
    {
        return $this->storage;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $idx = array_search($this->getId(), static::$aliveSessionIds);
        if ($idx === false) {
            $this->getLogger()->warning("A invalid session id found! May cause a little mem leak.", [
                'id' => $this->getId(),
            ]);
            return;
        }
        unset(static::$aliveSessionIds[$idx]);
    }

    /**
     * Calls once when created a new instance
     * @return string|float|int|bool|null
     */
    protected function _getId()
    {
        while (1) {

            $id = Uuid::uuid4();

            if (array_search($id, static::$aliveSessionIds) === false) {
                return $id;
            }

            $this->getLogger()->debug("Generated the same random session uuid!", [
                'id' => $id,
            ]);

        }
    }

    /**
     * @return string|float|int|bool|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SessionManagerInterface
     */
    public function getSessionManager(): SessionManagerInterface
    {
        return $this->sessionManager;
    }

    /**
     * @return NetworkManagerInterface
     */
    public function getNetworkManager(): NetworkManagerInterface
    {
        return $this->getSessionManager()->getNetworkManager();
    }
}