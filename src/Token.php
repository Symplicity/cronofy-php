<?php

namespace Cronofy;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\Interfaces\TokenInterface;

class Token implements TokenInterface
{
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
     * @throws CronofyException
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

            $token = $this->connection->post('oauth/token', $postFields);
            $token = Response::toArray($token);
            $this->set($token);
            return true;
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode());
        }
        return false;
    }

    /**
     *  The refresh_token issued to you when the user authorized your access to their account. REQUIRED
     * @return bool
     * @throws CronofyException
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
            $token = $this->connection->post('/oauth/token', $postFields);
            $token = Response::toArray($token);
            $this->set($token);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * The link_token to explicitly link to a pre-existing account. Details are available in the Cronofy API Documentation
     * @return mixed
     * @throws CronofyException
     */
    public function requestLinkToken()
    {
        try {
            $links = $this->connection->post('/' . Cronofy::API_VERSION . '/link_tokens');
            return $links;
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Either the refresh_token or access_token for the authorization you wish to revoke
     * @param string $token
     * @return mixed
     * @throws CronofyException
     */
    public function revoke(string $token)
    {
        $postFields = array(
            'client_id' => $this->connection->getClientId(),
            'client_secret' => $this->connection->getClientSecret(),
            'token' => $token
        );

        try {
            return $this->connection->post('/oauth/token/revoke', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode());
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

    public function getAuthorizationUrl(array $params) : string
    {
        $scope_list = rawurlencode(join(" ", $params['scope']));
        $url = $this->connection->getAppRootUrl() . '/oauth/authorize?response_type=code&client_id=' . $this->connection->getClientId() . '&redirect_uri=' . urlencode($params['redirect_uri']) . '&scope=' . $scope_list;
        if (!empty($params['state'])) {
            $url .= '&state=' . $params['state'];
        }
        if (!empty($params['avoid_linking'])) {
            $url .= '&avoid_linking=' . $params['avoid_linking'];
        }
        if (!empty($params['link_token'])) {
            $url.= '&link_token=' . $params['link_token'];
        }

        return $url;
    }

    public function getEnterpriseConnectAuthorizationUrl(array $params) : string
    {
        $scope_list = rawurlencode(join(" ", $params['scope']));
        $delegated_scope_list = rawurlencode(join(" ", $params['delegated_scope']));

        $url = $this->connection->getAppRootUrl() . '/enterprise_connect/oauth/authorize?response_type=code&client_id=' . $this->connection->getClientId() . '&redirect_uri=' . urlencode($params['redirect_uri']) . '&scope=' . $scope_list . '&delegated_scope=' . $delegated_scope_list;
        if (!empty($params['state'])) {
            $url .= '&state=' . rawurlencode($params['state']);
        }
        return $url;
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