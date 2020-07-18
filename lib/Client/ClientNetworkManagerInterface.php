<?php


namespace Konfigurator\Network\Client;


use Amp\CancelledException;
use Amp\Failure;
use Amp\Promise;
use Amp\Socket\ConnectException;
use Amp\Socket\SocketAddress;
use Konfigurator\Common\Enums\StateEnum;
use Konfigurator\Common\Exceptions\PendingShutdownError;
use Konfigurator\Network\Client\NetworkManager\ConnectionEventEnum;
use Konfigurator\Network\Client\NetworkManager\ConnectionStateEnum;
use Konfigurator\Network\NetworkManagerInterface;

interface ClientNetworkManagerInterface extends NetworkManagerInterface
{
    /**
     * @return ConnectionStateEnum
     */
    public function getState(): StateEnum;

    /**
     * @return Promise<ConnectionEventEnum|null>|Failure<PendingShutdownError>
     */
    public function awaitEvent(): Promise;

    /**
     * @param SocketAddress $address
     * @param int $timeout
     * @return Promise<void>|Failure<ConnectException|CancelledException>
     */
    public function connect(SocketAddress $address, int $timeout = 1000): Promise;

    /**
     * @return void
     */
    public function disconnect(): void;

    /**
     * @param string|\Stringable $packet
     * @return Promise<void>|Failure<ConnectException>
     */
    public function sendPacket($packet): Promise;

    /**
     * @return Promise<string>|Failure<ConnectException>
     */
    public function receivePacket(): Promise;
}