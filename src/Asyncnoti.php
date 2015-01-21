<?php
namespace Asyncnoti;

/**
 * Class Asyncnoti
 * @package Asyncnoti
 *
 * Proxy class allow isolate some methods from AsyncnotiMain class.
 */
class Asyncnoti
{
    private $instance;

    public function __construct($appKey = '', $appSecret = '', $appId = '', $host = 'http://asyncnoti.com',
                                $port = 80, $timeout = null)
    {
        $this->instance = new AsyncnotiMain($appKey, $appSecret, $appId, $host, $port, $timeout);
    }

    public function trigger($channels, $eventName, $data = null)
    {
        return $this->instance->trigger($channels, $eventName, $data);
    }
}