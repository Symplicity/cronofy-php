<?php

namespace Cronofy;

use Cronofy\Exception\CronofyException;
use Cronofy\Interfaces\ConnectionInterface;

class User
{

    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException
     */
    public function elevatePermissions(array $params)
    {
        try {
            return $this->connection->post(Cronofy::API_VERSION . '/permissions', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e);
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function authorizeWithServiceAccount(array $params)
    {
        if (empty($params['scope']) || empty($params['email']) || empty($params['callback_url'])) {
            throw new \InvalidArgumentException('Missing required params.');
        }

        if (gettype($params['scope']) == 'array') {
            $params['scope'] = join(' ', $params['scope']);
        }

        try {
            return $this->connection->post(Cronofy::API_VERSION . '/service_account_authorizations', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e);
        }
    }
}