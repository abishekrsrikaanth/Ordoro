<?php


namespace Ordoro\Core;


use GuzzleHttp\Client;
use Ordoro\Core\Contracts\Requestable;
use Ordoro\Core\Exceptions\RequestException;
use GuzzleHttp\Psr7\Request as HttpRequest;

class Request implements Requestable
{
    /**
     * @var string
     */
    const API_URL = 'https://api.ordoro.com';

    /**
     * @var
     */
    private $username;
    /**
     * @var
     */
    private $password;
    /**
     * @var Client
     */
    private $client;

    /**
     * Request constructor.
     * @param $username
     * @param $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->client = new Client([
            'base_uri' => self::API_URL
        ]);
    }

    /**
     * @param $method
     * @param $url
     * @param array $data
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws RequestException
     */
    public function send($method, $url, array $data = [])
    {
        $methodUpper = strtoupper($method);

        $request = new HttpRequest($methodUpper, $url, [
            'auth' => [$this->username, $this->password]
        ], $this->safeJsonEncode($data));

        $response = $this->client->send($request);

        return new Response($response);
    }

    /**
     * @param $mixed
     * @return false|string
     * @throws RequestException
     */
    public function safeJsonEncode($mixed)
    {
        $encoded = json_encode($mixed);
        $error = json_last_error();
        switch ($error) {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                RequestException::onMaximumDepthExceeded();
            case JSON_ERROR_UTF8:
                $clean = $this->utf8ize($mixed);
                return $this->safeJsonEncode($clean);
            default:
                RequestException::onErrorEncodingJson($error);
        }
    }

    /**
     * @param $mixed
     * @return array|string
     */
    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string($mixed)) {
            return utf8_encode($mixed);
        }
        return $mixed;
    }

    /**
     * @param $response
     * @param $curl
     * @param $code
     *
     * @throws RequestException
     */
    private function handleHttpStatusError($response, $curl, $code)
    {
        $parsed = json_decode($response);
        if ($parsed === null) {
            curl_close($curl);
            RequestException::onErrorProcessingRequest($code);
        }
        $errCode = '';
        $errMessage = '';
        $errType = '';
        if (isset($parsed->meta->code)) {
            $errCode = $parsed->meta->code;
        }
        if (isset($parsed->meta->message)) {
            $errMessage = $parsed->meta->message;
        }
        if (isset($parsed->meta->type)) {
            $errType = $parsed->meta->type;
        }
        curl_close($curl);
        throw new AfterShipException("$errType: $errCode - $errMessage", $errCode);
    }
}