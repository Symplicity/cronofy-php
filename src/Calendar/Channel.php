<?php

namespace Cronofy\Calendar;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\Connection;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;

class Channel
{
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function createChannel(array $params = [])
    {
        if (empty($params['callback_url'])) {
            throw new \InvalidArgumentException('Missing callback url.');
        }

        $postFields = array('callback_url' => $params['callback_url']);

        if (!empty($params['filters'])) {
            $postFields['filters'] = $params['filters'];
        }

        try {
            $response = $this->connection->post('/' . self::API_VERSION . '/channels', $postFields);
            return Connection::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    /**
     * @return array Array of channels
     * @throws CronofyException
     */
    public function listChannels()
    {
        try {
            $response = $this->connection->get('/' . self::API_VERSION . '/channels');
            return Connection::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function closeChannel(array $params = [])
    {
        if (empty($params['channel_id'])) {
            throw new \InvalidArgumentException('Missing channel id');
        }

        try {
            $response = $this->connection->delete('/' . self::API_VERSION . '/channels' . $params['channel_id']);
            return Connection::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }
}
