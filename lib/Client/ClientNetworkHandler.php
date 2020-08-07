<?php


namespace Konfigurator\Network\Client;


use Amp\CancelledException;
use Amp\Delayed;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket;
use Amp\Socket\ConnectContext;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\SocketAddress;
use Amp\Success;
use Konfigurator\Network\AbstractNetworkHandler;
use Konfigurator\Network\NetworkEventDispatcher;
use Konfigurator\Network\NetworkHandlerState;
use function Amp\asyncCall;
use function Amp\call;

class ClientNetworkHandler extends AbstractNetworkHandler implements ClientNetworkHandlerInterface
{
    const MAX_PACKET_LENGTH = 1024 * 1024 * 4;

    /** @var ResourceSocket|null */
    protected ?ResourceSocket $clientHandler;

    /** @var SocketAddress|null */
    protected ?SocketAddress $address;

    /** @var int */
    protected int $packetCounter;

    /** @var string[]|array<int, array<int, string>> */
    protected array $unprocessedPackets;


    /**
     * ClientNetworkHandler constructor.
     * @param NetworkEventDispatcher $eventDispatcher
     */
    public function __construct(NetworkEventDispatcher $eventDispatcher)
    {
        parent::__construct($eventDispatcher);

        $this->clientHandler = null;
        $this->address = null;

        $this->packetCounter = 0;
        $this->unprocessedPackets = [];
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
        parent::shutdown();

        $this->disconnect();
    }

    /**
     * @param ResourceSocket $connection
     * @param NetworkEventDispatcher $eventDispatcher
     * @return Promise<static>
     */
    public static function fromServerConnection(ResourceSocket $connection, NetworkEventDispatcher $eventDispatcher): Promise
    {
        return call(static function (ResourceSocket $connection, NetworkEventDispatcher $eventDispatcher) {

            try {

                $instance = new static($eventDispatcher);

                $instance->clientHandler = $connection;
                $instance->address = $connection->getRemoteAddress();

                $instance->setState(NetworkHandlerState::RUNNING());
                yield $instance->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::CONNECTED($instance));

                return $instance;

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $connection, $eventDispatcher);
    }

    /**
     * @return SocketAddress|null
     */
    public function getAddress(): ?SocketAddress
    {
        return $this->address;
    }

    /**
     * @param SocketAddress $address
     * @param ConnectContext|null $connectContext
     * @return Promise<void>|Failure<ConnectException|CancelledException>
     */
    public function connect(SocketAddress $address, ?ConnectContext $connectContext = null): Promise
    {
        if ($this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return new Success();
        }

        return call(static function (self &$self, SocketAddress $address, ?ConnectContext $connectContext = null) {

            try {

                $self->getLogger()->debug("Connecting to tcp://{$address}...");

                $self->clientHandler = yield $self->createConnectionHandler($address, $connectContext);
                $self->address = $self->clientHandler->getLocalAddress();

                $self->getLogger()->info("Connection successful established with tcp://{$address}!");

                //yield new Delayed(0);

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::connect exception!", [
                    'exception' => $e,
                ]);

                return new Failure($e);

            }

            $self->setState(NetworkHandlerState::RUNNING());
            $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::CONNECTED($self));

        }, $this, $address, $connectContext);
    }

    /**
     * @return void
     */
    public function disconnect(): void
    {
        if (!$this->getState()->equals(NetworkHandlerState::RUNNING())) {
            return;
        }

        $this->getLogger()->info("Connection closed!");

        $this->setState(NetworkHandlerState::STOPPED());

        if ($this->clientHandler) {
            if (!$this->clientHandler->isClosed()) {
                $this->clientHandler->close();
            }
            $this->clientHandler = null;
        }

        $this->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::DISCONNECTED($this));
    }

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>|Failure<ConnectException>
     */
    public function sendPacket($packet): Promise
    {
        return call(static function (self &$self, $packet) {

            if (!$self->getState()->equals(NetworkHandlerState::RUNNING())) {
                return new ConnectException("Client is not connected!");
            }

            try {

                $packetId = $self->packetCounter++;
                $packetLength = strlen($packet);
                $chunkId = 0;
                $sendBytes = 0;
                $chunkLength = 8180;
                //$chunksCount = intval(ceil($packetLength / $chunkLength));

                $promises = [];

                do {

                    $chunk = substr($packet, $sendBytes, $chunkLength);
                    $chunk = pack("NNnn", $packetId, $packetLength, strlen($chunk), $chunkId++) . $chunk;

                    //dump(strlen($chunk));

                    $promises[] = $self->clientHandler->write($chunk);

                    $sendBytes += $chunkLength;

                } while ($sendBytes < $packetLength);

                yield Promise\all($promises);

                $self->getLogger()->debug("SEND: packet length {$packetLength}");

            } catch (\Throwable $e) {

                $self->getLogger()->debug(__CLASS__ . "::sendPacket exception!", [
                    'exception' => $e,
                ]);

                $self->disconnect();

                return new Failure($e);
            }

        }, $this, $packet);
    }

    /**
     * @return Promise<void>
     */
    protected function _handle(): Promise
    {
        return call(static function (self &$self) {

            if (empty($self->clientHandler) || $self->clientHandler->isClosed()) {
                $self->disconnect();
                return;
            }

            $packet = yield $self->clientHandler->read();

            if ($packet && strlen($packet) > 12) {

                $unpack = unpack('NpacketId/NpacketLength/nchunkLength/nchunkId', substr($packet, 0, 12));
                //dump($unpack);

                $packetId = $unpack['packetId'];
                $packetLength = $unpack['packetLength'];
                $chunkLength = $unpack['chunkLength'];
                $chunkId = $unpack['chunkId'];

                if ($packetLength < 1 || $packetLength > static::MAX_PACKET_LENGTH || $chunkLength < 1) {
                    if (isset($self->unprocessedPackets[$packetId])) {
                        unset($self->unprocessedPackets[$packetId]);
                    }
                    $self->getLogger()->debug("RECV: invalid packet!");
                    return;
                }

                if ($chunkId == 0 && $chunkLength == $packetLength) {

                    $packet = substr($packet, 12);

                    $self->getLogger()->debug("RECV: packet length " . strlen($packet));

                    yield $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_RECEIVED($self, $packet));
                    return;

                }

                $packet = substr($packet, 12);

                if (strlen($packet) != $chunkLength) {
                    if (isset($self->unprocessedPackets[$packetId])) {
                        unset($self->unprocessedPackets[$packetId]);
                    }
                    $self->getLogger()->debug("RECV: invalid packet!");
                    return;
                }

                if (!isset($self->unprocessedPackets[$packetId])) {
                    $self->unprocessedPackets[$packetId] = [
                        'recvLength' => 0,
                        'packetLength' => $packetLength,
                        'chunks' => [],
                    ];
                }

                $self->unprocessedPackets[$packetId]['recvLength'] += $chunkLength;
                $self->unprocessedPackets[$packetId]['chunks'][$chunkId] = $packet;

                if ($self->unprocessedPackets[$packetId]['recvLength'] == $self->unprocessedPackets[$packetId]['packetLength']) {

                    ksort($self->unprocessedPackets[$packetId]['chunks'], SORT_NUMERIC);
                    $packet = implode('', $self->unprocessedPackets[$packetId]['chunks']);
                    unset($self->unprocessedPackets[$packetId]);

                    \gc_collect_cycles();

                    $self->getLogger()->debug("RECV: packet length " . strlen($packet));

                    yield $self->getEventDispatcher()->dispatch(ClientNetworkHandlerEvent::PACKET_RECEIVED($self, $packet));
                    return;

                } else if ($self->unprocessedPackets[$packetId]['recvLength'] >= $self->unprocessedPackets[$packetId]['packetLength']) {

                    unset($self->unprocessedPackets[$packetId]);
                    $self->getLogger()->debug("RECV: invalid packet!");
                    return;

                }

            }

            //yield new Delayed(0);

        }, $this);
    }

    /**
     * @param SocketAddress $address
     * @param ConnectContext|null $connectContext
     *
     * @return Promise<ResourceSocket>
     *
     * @throws ConnectException
     * @throws CancelledException
     */
    protected function createConnectionHandler(SocketAddress $address, ?ConnectContext $connectContext = null): Promise
    {
        return Socket\connect("tcp://{$address}", $connectContext);
    }
}