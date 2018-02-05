<?php

namespace Cronofy\Interfaces;

interface ConnectionInterface
{
    public function postTo(string $uri, array $params = []);
    public function getClientSecret() : string;
    public function getClientId() : string;
    public function getApiRootUrl() : string;
    public function getAppRootUrl() : string;
    public function getHostDomain() : string;
}