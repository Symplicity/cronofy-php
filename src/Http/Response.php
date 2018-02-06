<?php

namespace Cronofy\Http;

class Response
{
    public static function toArray(\GuzzleHttp\Psr7\Response $response) : array
    {
        $body = $response->getBody();
        return json_decode($body, true);
    }
}