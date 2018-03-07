<?php

namespace Cronofy\Http;

class Response
{
    public static function toArray(\GuzzleHttp\Psr7\Response $response) : array
    {
        $body = $response->getBody()->getContents();
        $body = json_decode($body, true);
        if ($body === null) {
            return [];
        }
        return $body;
    }
}
