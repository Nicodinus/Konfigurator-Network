<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;
use function Amp\call;

abstract class AbstractInputPacket extends AbstractPacket implements InputPacketInterface
{
    /** @var PacketTransformerInterface[] */
    private array $inputTransformers;

    /**
     * AbstractInputPacket constructor.
     * @param SessionInterface $session
     */
    protected function __construct(SessionInterface $session)
    {
        parent::__construct($session);

        $this->inputTransformers = $this->getInputTransformers();
    }

    /**
     * @param SessionInterface $session
     * @param mixed $inputPacket
     *
     * @return Promise<static>
     */
    public static function fromRemote(SessionInterface $session, $inputPacket): Promise
    {
        return call(static function (SessionInterface $session, $inputPacket) {

            try {

                $instance = new static($session);

                foreach ($instance->inputTransformers as $transformer) {
                    $inputPacket = $transformer::transform($instance, $inputPacket);
                }

                /** @var OutputPacketInterface|null $response */
                $response = yield $instance->handleInputPacket($inputPacket);
                if (!empty($response)) {
                    yield $session->sendPacket($response);
                }

                return $instance;

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $session, $inputPacket);
    }

    /**
     * @return PacketTransformerInterface[]
     */
    protected abstract function getInputTransformers(): array;

    /**
     * @param mixed $inputPacket
     *
     * @return Promise<OutputPacketInterface|null>|Failure<\Throwable>
     *
     * @throws \Throwable
     */
    protected abstract function handleInputPacket($inputPacket): Promise;
}