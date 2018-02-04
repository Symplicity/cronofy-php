<?php

namespace Cronofy;

use Cronofy\Interfaces\ConnectionInterface;

class Token
{
    public const API_VERSION = 'v1';

    private $refreshToken;
    private $accessToken;
    private $expiresIn;
    private $connection;

    public $error;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $params
     * @return bool
     * @throws \CronofyException
     */
    public function request(array $params = []) : bool
    {
        try {
            $postFields = [
                'client_id' => $this->connection->getClientId(),
                'client_secret' => $this->connection->getClientSecret(),
                'grant_type' => 'authorization_code',
                'code' => $params['code'],
                'redirect_uri' => $params['redirect_uri']
            ];

            $token = $this->connection->postTo('oauth/token', $postFields);

            $this->set($token);
        } catch (\Exception $e) {
            throw new \CronofyException($e);
        }
        return false;
    }

    /**
     *  The refresh_token issued to you when the user authorized your access to their account. REQUIRED
     * @return bool
     * @throws \CronofyException
     */
    public function refresh() : bool
    {
        $postFields = array(
            'client_id' => $this->connection->getClientId(),
            'client_secret' => $this->connection->getClientSecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
        );

        try {
            $token = $this->connection->postTo('/oauth/token', $postFields);
            $this->set($token);
        } catch (\Exception $e) {
            throw new \CronofyException($e);
        }
    }

    /**
     * The link_token to explicitly link to a pre-existing account. Details are available in the Cronofy API Documentation
     * @return mixed
     * @throws \CronofyException
     */
    public function requestLinkToken()
    {
        try {
            $links = $this->connection->postTo('/' . self::API_VERSION . '/link_tokens');
            return $links;
        } catch (\Exception $e) {
            throw new \CronofyException($e);
        }
    }

    /**
     * Either the refresh_token or access_token for the authorization you wish to revoke
     * @param string $token
     * @return mixed
     * @throws \CronofyException
     */
    public function revoke(string $token)
    {
        $postFields = array(
            'client_id' => $this->connection->getClientId(),
            'client_secret' => $this->connection->getClientSecret(),
            'token' => $token
        );

        try {
            return $this->connection->postTo('/oauth/token/revoke', $postFields);
        } catch (\Exception $e) {
            throw new \CronofyException($e);
        }
    }

    public function set(array $token) : bool
    {
        if (!empty($token['access_token'])) {
            $this->setAccessToken($token['access_token']);
            $this->setRefreshToken($token['refresh_token']);
            $this->setExpiresIn($token['expires_in']);
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param mixed $accessToken
     */
    private function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param mixed $refreshToken
     */
    private function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return mixed
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    /**
     * @param mixed $expiresIn
     */
    private function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    private function setError(\Exception $e) {
        $this->error = $e->getMessage();
    }

    public function getError()
    {
        return $this->error;
    }
}