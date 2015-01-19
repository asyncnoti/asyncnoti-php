<?php
namespace Asyncnoti;

class Asyncnoti
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
            throw new \Exception("Key should be string");

        if(!is_string($appSecret))
            throw new \Exception("App secret should be string");

        if(!is_numeric($appId))
            throw new \Exception("App id should be integer");

        if(!is_string($host))
            throw new \Exception("Host should be string");

        if(!is_numeric($port))
            throw new \Exception("Port should be integer");

        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->appId = $appId;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function trigger($channels, $eventName, $data = null)
    {
        if(is_null($data))
            $data = new \stdClass();

        if(!is_array($channels))
            $channels = array($channels);

        $dataJson = json_encode($data);

        list($status, $rawResponse) = $this->request(
            sprintf('/api/v1/apps/%s/events', $this->appId), 'POST', array(
            'data' => $dataJson,
            'data_hash' => hash('sha256', $dataJson),
            'name' => $eventName,
            'channels' => $channels
        ));

        if($status < 200 || $status >= 299)
            throw new \Exception('Invalid response');

        return json_decode($rawResponse, true);
    }

    private function request($uri, $method, $params = array())
    {
        $params['auth_timestamp'] = date('U');
        $params['auth_key'] = $this->appKey;

        $stringToSign = implode("\n", array(
            $method,
            $uri,
            $this->makeQueryString($params)
        ));

        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $this->appSecret);

        return $this->http_request($method, $uri, $params);
    }

    private function http_request($method, $uri, $requestParams)
    {
        $requestBody = json_encode($requestParams);
        $headers = array('Content-Type: application/json');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, implode('', array($this->host, $uri)));
        curl_setopt($curl, CURLOPT_PORT, $this->port);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        $response = curl_exec($curl);

        $info = curl_getinfo($curl);
        curl_close($curl);

        return array($info['http_code'], $response);
    }

    private function makeQueryString($params)
    {
        $builds = array();
        ksort($params);
        foreach($params as $key=>$value)
        {
            if (is_array($value))
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