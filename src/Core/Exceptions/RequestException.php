<?php

namespace Ordoro\Core\Exceptions;

class RequestException extends \HttpRequestException
{
    public static function onErrorProcessingRequest($code)
    {
        throw new static("Error processing request - received HTTP error code $code", $code);
    }

    public static function onMaximumDepthExceeded()
    {
        throw new static('Maximum stack depth exceeded');
    }

    public static function onErrorEncodingJson($error)
    {
        throw new static("json_encode Error: $error");
    }
}