<?php
namespace Asyncnoti;

/**
 * Class AsyncnotiMain
 * @package Asyncnoti
 *
 * all methods is public for simplify testing mock
 */
class AsyncnotiMain
{
    private $appKey;
    private $appSecret;
    private $appId;
    private $host;
    private $port;
    private $timeout;

    public function __construct($appKey = '', $appSecret = '', $appId = '', $host = 'http://asyncnoti.com',
                                $port = 80, $timeout = null)
    {
        if(is_null($timeout))
            $timeout = ini_get('default_socket_timeout');

        if(!$timeout)
            $timeout = 60;

        if(!is_string($appKey))
            throw new \InvalidArgumentException("Key should be string");

        if(!is_string($appSecret))
            throw new \InvalidArgumentException("App secret should be string");

        if(!is_string($appId))
            throw new \InvalidArgumentException("App id should be string");

        if(!is_string($host))
            throw new \InvalidArgumentException("Host should be string");

        if(!is_numeric($port))
            throw new \InvalidArgumentException("Port should be number");

        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->appId = $appId;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Method made public for simple mocking in unit test
     *
     * @return bool|string
     */
    public function _getMicroseconds()
    {
        return date('U');
    }

    public function trigger($channels, $eventName, $data = null)
    {
        if(is_null($data))
            $data = new \stdClass();

        if(!is_array($channels))
            $channels = array($channels);

        if(!is_string($eventName))
            throw new \InvalidArgumentException("event_name must be integer");

        if(strlen($eventName) > 255)
            throw new \LengthException("event_name too long");

        foreach($channels as $channel)
        {
            if(!is_string($channel))
                throw new \InvalidArgumentException("Channel should be");

            if(strlen($channel) > 255)
                throw new \LengthException("Channel too long");
        }

        $dataJson = json_encode($data);

        return $this->_request(
            sprintf('/api/v1/apps/%s/events', $this->appId), 'POST', array(
            'data' => $dataJson,
            'data_hash' => hash('sha256', $dataJson),
            'name' => $eventName,
            'channels' => $channels
        ));
    }

    public function _request($uri, $method, $params = array())
    {
        $params['auth_timestamp'] = $this->_getMicroseconds();
        $params['auth_key'] = $this->appKey;

        $stringToSign = implode("\n", array(
            $method,
            $uri,
            $this->_makeQueryString($params)
        ));

        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $this->appSecret);

        list($status, $rawResponse) = $this->_httpRequest($method, $uri, $params);

        if($status >= 300 || $status < 200)
            throw new AsyncnotiException('Asyncnoti HTTP error '.$status, $status);

        return json_decode($rawResponse, true);
    }

    public function _httpRequest($method, $uri, $requestParams)
    {
        $requestBody = json_encode($requestParams);
        $headers = array('Content-Type: application/json');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, implode('', array($this->host, $uri)));
        curl_setopt($curl, CURLOPT_PORT, $this->port);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        $rawResponse = curl_exec($curl);

        $info = curl_getinfo($curl);
        curl_close($curl);

        return array($info['http_code'], $rawResponse);
    }

    public function _makeQueryString($params)
    {
        $builds = array();
        ksort($params);
        foreach($params as $key => $value)
        {
            if(is_array($value))
            {
                foreach($value as $item)
                    $builds[] = sprintf('%s[]=%s', $key, $item);
            }
            else
                $builds[] = sprintf('%s=%s', $key, $value);
        }

        return implode('&', $builds);
    }
}