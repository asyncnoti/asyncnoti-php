<?php

use Asyncnoti\AsyncnotiMain;

class AsyncnotiRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testTriggerMethod()
    {
        $self = $this; // need for PHP less 5.4
        $mock = $this->getAsyncnotiMock(array('_request'));

        $mock->expects($this->once())
            ->method('_request')
            ->withAnyParameters()
            ->will($this->returnCallback(function ($uri, $method, $params = array()) use ($self)
            {
                $self->assertEquals('/api/v1/apps/123/events', $uri);
                $self->assertEquals('POST', $method);
                $self->assertEquals('{"data":"{}",'.
                    '"data_hash":"44136fa355b3678a1146ad16f7e8649e94fb4fc21fe77e8310c060f61caaff8a",'.
                    '"name":"event1","channels":["channel1"]}', json_encode($params));

                return array();
            }));

        $result = $mock->trigger('channel1', 'event1');
        $this->assertEquals(array(), $result);
    }

    public function testRequestMethod()
    {
        $self = $this; // need for PHP less 5.4
        $mock = $this->getAsyncnotiMock(array('_httpRequest', '_getMicroseconds'));

        $mock->expects($this->once())
            ->method('_getMicroseconds')
            ->will($this->returnValue(1421420862));

        $mock->expects($this->once())
            ->method('_httpRequest')
            ->withAnyParameters()
            ->will($this->returnCallback(function ($method, $uri, $requestParams) use ($self)
            {
                $self->assertEquals('POST', $method);
                $self->assertEquals('/api/v1/apps/123/events', $uri);
                //                echo PHP_EOL.json_encode($requestParams).PHP_EOL;
                $self->assertEquals('{"data":"{}",'.
                    '"data_hash":"44136fa355b3678a1146ad16f7e8649e94fb4fc21fe77e8310c060f61caaff8a",'.
                    '"name":"event1","channels":["channel1"],"auth_timestamp":1421420862,"auth_key":"key123",'.
                    '"auth_signature":"6a5f929fb5a80ba7f7e7f19664b7ba1a5a02adf245bf0f79cef767d8c0c1ed34"}',
                    json_encode($requestParams));

                return array(200, '{}');
            }));


        $result = $mock->trigger('channel1', 'event1');
        $this->assertEquals(array(), $result);
    }

    /**
     * @expectedException \Asyncnoti\AsyncnotiException
     * @expectedExceptionCode 405
     */
    public function testRequestError()
    {
        $mock = $this->getAsyncnotiMock(array('_httpRequest'));

        $mock->expects($this->once())
            ->method('_httpRequest')
            ->withAnyParameters()
            ->will($this->returnValue(array(405, '{}')));

        $mock->trigger('channel1', 'event1');
    }

    /**
     * @param array $methods
     *
     * @return PHPUnit_Framework_MockObject_MockObject|AsyncnotiMain
     */
    private function getAsyncnotiMock($methods = array())
    {
        return $this->getMockBuilder('\Asyncnoti\AsyncnotiMain')
            ->setConstructorArgs(array('key123', 'secret123', '123'))
            ->setMethods($methods)
            ->getMock();
    }

}