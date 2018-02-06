<?php

namespace Cronofy\Interfaces;

use GuzzleHttp\Psr7\Response;

interface ConnectionInterface
{
    public function post(string $url, array $params = []);
    public function get(string $url, array $params = []);
    public function delete(string $url, array $params = []);
    public static function toArray(Response $response) : array;
    public function getClientSecret() : string;
    public function getClientId() : string;
    public function getApiRootUrl() : string;
    public function getAppRootUrl() : string;
    public function getHostDomain() : string;
}