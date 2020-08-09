<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;
use Konfigurator\Network\Session\SessionInterface;
use function Amp\call;

abstract class AbstractOutputPacket extends AbstractPacket implements OutputPacketInterface
{
    /** @var PacketTransformerInterface[] */
    private array $outputTransformers;

    /**
     * AbstractOutputPacket constructor.
     * @param SessionInterface $session
     */
    protected function __construct(SessionInterface $session)
    {
        parent::__construct($session);

        $this->outputTransformers = $this->getOutputTransformers();
    }

    /**
     * @return Promise<string>|Failure<\Throwable>
     */
    public function transform(): Promise
    {
        return call(static function (self &$self) {

            try {

                $data = yield $self->_transform();

                foreach ($this->getOutputTransformers() as $transformer) {
                    $data = yield $transformer::transform($self, $data);
                }

                return $data;

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @return Promise
     */
    public function send(): Promise
    {
        return $this->getSession()->sendPacket($this);
    }

    /**
     * @return Promise<mixed>|Failure<\Throwable>
     */
    protected abstract function _transform(): Promise;

    /**
     * @return PacketTransformerInterface[]
     */
    protected abstract function getOutputTransformers(): array;
}