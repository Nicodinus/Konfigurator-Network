<?php


namespace Konfigurator\Network\Packet;


use Amp\Failure;
use Amp\Promise;

interface PacketTransformerInterface
{
    /**
     * @param PacketInterface $packet
     * @param mixed $data
     * @return Promise<mixed>|Failure<\Throwable>
     */
    public static function transform(PacketInterface $packet, $data): Promise;
}