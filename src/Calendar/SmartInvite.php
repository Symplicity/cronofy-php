<?php

namespace Cronofy\Calendar;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;

class SmartInvite
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
    public function createSmartInvite(array $params)
    {
        if (empty($params['recipient']) || empty($params['event']) || empty($params['smart_invite_id']) || empty($params['callback_url'])) {
            throw new \InvalidArgumentException('Missing required parameters');
        }

        $postFields = array(
            'recipient' => $params['recipient'],
            'event' => $params['event'],
            'smart_invite_id' => $params['smart_invite_id'],
            'callback_url' => $params['callback_url'],
        );

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/smart_invites', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function cancelSmartInvite(array $params)
    {
        if (empty($params['recipient']) || empty($params['smart_invite_id'])) {
            throw new \InvalidArgumentException('Missing required parameters');
        }

        $postFields = array(
            'recipient' => $params['recipient'],
            'smart_invite_id' => $params['smart_invite_id'],
            'method' => 'cancel',
        );

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/smart_invites', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }

    /**
     * @param string $smartInviteId
     * @param string $recipientEmail
     * @return mixed
     * @throws CronofyException
     */
    public function getSmartInvite($smartInviteId, $recipientEmail)
    {
        $urlParams = array(
            'smart_invite_id' => $smartInviteId,
            'recipient_email' => $recipientEmail,
        );

        try {
            return $this->connection->get('/' . Cronofy::API_VERSION . '/smart_invites', $urlParams);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }
}
