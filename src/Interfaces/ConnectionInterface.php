<?php

namespace Cronofy\Interfaces;

interface ConnectionInterface
{
    public function post(string $url, array $params = []);
    public function get(string $url, array $params = []);
    public function delete(string $url, array $params = []);
    public function getClientSecret() : string;
    public function getClientId() : string;
    public function getApiRootUrl() : string;
    public function getAppRootUrl() : string;
    public function getHostDomain() : string;
}
