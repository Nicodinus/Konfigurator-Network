<?php


namespace Konfigurator\Network;


use Konfigurator\Common\Enums\EventEnum;

/**
 * Class NetworkHandlerEvent
 * @package Konfigurator\Network
 * @method static static EMPTY()
 */
class NetworkHandlerEvent extends EventEnum
{
    /** @var NetworkHandlerInterface|null */
    protected ?NetworkHandlerInterface $handler;


    /**
     * NetworkHandlerEvent constructor.
     * @param $enumValue
     * @param NetworkHandlerInterface|null $handler
     * @param mixed $eventData
     */
    public function __construct($enumValue, ?NetworkHandlerInterface $handler = null, $eventData = null)
    {
        parent::__construct($enumValue, $eventData);

        $this->handler = $handler;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        $array = static::toArray();
        if (isset($array[$name]) || \array_key_exists($name, $array)) {
            return new static($array[$name], ...$arguments);
        }

        throw new \BadMethodCallException("No static method or enum constant '$name' in class " . static::class);
    }

    /**
     * @return NetworkHandlerInterface|null
     */
    public function getNetworkHandler(): ?NetworkHandlerInterface
    {
        return $this->handler;
    }
}