<?php

class AsyncnotiInstanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBadKey()
    {
        new \Asyncnoti\Asyncnoti(123);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBadSecret()
    {
        new \Asyncnoti\Asyncnoti('key', 5134);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBadAppId()
    {
        new \Asyncnoti\Asyncnoti('key', 'secret', 123);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBadHostname()
    {
        new \Asyncnoti\Asyncnoti('key', 'secret', '1156', 4532);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBadPort()
    {
        new \Asyncnoti\Asyncnoti('key', 'secret', '1156', 'http://hostname', 'port');
    }
}