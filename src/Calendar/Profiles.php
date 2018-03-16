<?php

namespace Cronofy\Calendar;

use Cronofy\Cronofy;
use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;

class Profiles
{
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getAccount()
    {
        try {
            $response = $this->connection->get('/' . Cronofy::API_VERSION . '/account');
            return Response::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    public function getUserInfo()
    {
        try {
            $response = $this->connection->get('/' . Cronofy::API_VERSION . '/userinfo');
            return Response::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    public function getProfiles()
    {
        try {
            $response = $this->connection->get('/' . Cronofy::API_VERSION . '/profiles');
            return Response::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }
}
