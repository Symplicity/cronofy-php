<?php

namespace Cronofy\Interfaces;

interface TokenInterface
{
    public function request(array $params = []) : bool;
    public function refresh() : bool;
    public function revoke(string $token);
    public function requestLinkToken();
    public function set(array $token) : bool;
    public function getAccessToken();
    public function getRefreshToken();
}