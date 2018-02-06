<?php

namespace Cronofy\Http;

use Cronofy\Exception\InvalidUrlRequest;
use Cronofy\Interfaces\ConnectionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Class Connection
 * @property ClientInterface $client
 * @package Cronofy
 */
class Connection implements ConnectionInterface
{
    private $clientId;
    private $clientSecret;
    private $apiRootUrl;
    private $appRootUrl;
    private $hostDomain;
    private $accessToken;

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setClientId($config['client_id'] ?? null);
        $this->setClientSecret($config['client_secret'] ?? null);
        $this->setAccessToken($config['access_token'] ?? null);
        $this->setUrls();
    }

    private function setUrls()
    {
        $dataCenterAddin = empty($this->config['data_center']) ? '' : sprinf('-%s', $this->config['data_center']);
        $this->apiRootUrl = "https://api{$dataCenterAddin}.cronofy.com";
        $this->appRootUrl = "https://app{$dataCenterAddin}.cronofy.com";
        $this->hostDomain = "api{$dataCenterAddin}.cronofy.com";
    }

    public function __get($variable)
    {
        if ($variable === 'client') {
            $this->client = $this->config['http_client'] ?? new Client(['base_uri' => $this->apiRootUrl]);
            return $this->client;
        }
        return null;
    }

    public function post(string $url, array $params = [])
    {
        $headers = $this->getHeaders($params);
        return $this->client->request('POST', $url, [
            'form_params' => $params,
            'headers' => $headers
        ]);
    }

    public function get(string $url, array $params = [])
    {
        $headers = $this->getHeaders($params);
        return $this->client->request('GET', $url, [
            'headers' => $headers,
            'query' => $params
        ]);
    }

    public function delete(string $url, array $params = [])
    {
        $headers = $this->getHeaders($params);
        return $this->client->request('DELETE', $url, [
            'headers' => $headers,
            'params' => $params
        ]);
    }

    public function getHeaders(array &$params = []) : array
    {
        $headers = [];

        if ($this->accessToken !== null) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->accessToken);
        }

        $headers['host'] = $this->hostDomain;

        if (!empty($params['with_content_header'])) {
            $headers['Content-Type'] = 'application/json; charset=utf-8';
            unset($params['with_content_header']);
        }
        return $headers;
    }

    /**
     * @return mixed
     */
    public function getClientId() : string
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    private function setClientId(?string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret() : string
    {
        return $this->clientSecret;
    }

    /**
     * @param mixed $clientSecret
     */
    private function setClientSecret(?string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken(?string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return mixed
     */
    public function getApiRootUrl() : string
    {
        return $this->apiRootUrl;
    }

    /**
     * @return mixed
     */
    public function getAppRootUrl() : string
    {
        return $this->appRootUrl;
    }

    /**
     * @return mixed
     */
    public function getHostDomain() : string
    {
        return $this->hostDomain;
    }
}