<?php

namespace Cronofy\Calendar;

use Cronofy\Cronofy;
use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\Interfaces\ResponseIteratorInterface;

final class Calendar
{
    private $connection;
    private $responseIterator;

    public function __construct(ConnectionInterface $connection, ResponseIteratorInterface $responseIterator)
    {
        $this->connection = $connection;
        $this->responseIterator = $responseIterator;
    }

    public function listCalendars()
    {
        try {
            $response = $this->connection->get(Cronofy::API_VERSION . '/calendars');
            return Response::toArray($response);
        } catch (\Exception $e) {
        }
    }

    /**
     * @param array $params
     * @returns ResponseIteratorInterface
     * @throws CronofyException
     */
    public function readEvents(array $params = []) : ResponseIteratorInterface
    {
        try {
            $url = $this->getConnectionUrl() . '/events';
            return $this->responseIterator->setItems($url, 'events', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return ResponseIteratorInterface
     * @throws CronofyException
     */
    public function freeBusy(array $params) : ResponseIteratorInterface
    {
        try {
            $url = $this->getConnectionUrl() . '/free_busy';
            return $this->responseIterator->setItems($url, 'free_busy', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function upsertEvent(array $params)
    {
        if (empty($params['calendar_id'])) {
            throw new \InvalidArgumentException('Missing Calendar id in params.');
        }

        $postFields = array(
            'event_id' => $params['event_id'],
            'summary' => $params['summary'],
            'description' => $params['description'],
            'start' => $params['start'],
            'end' => $params['end']
        );

        if (!empty($params['tzid'])) {
            $postFields['tzid'] = $params['tzid'];
        }

        if (!empty($params['location'])) {
            $postFields['location'] = $params['location'];
        }

        if(!empty($params['reminders'])) {
            $postFields['reminders'] = $params['reminders'];
        }

        if(!empty($params['reminders_create_only'])) {
            $postFields['reminders_create_only'] = $params['reminders_create_only'];
        }

        if(!empty($params['transparency'])) {
            $postFields['transparency'] = $params['transparency'];
        }

        if(!empty($params['attendees'])) {
            $postFields['attendees'] = $params['attendees'];
        }

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/calendars/' . $params['calendar_id'] . '/events', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    public function deleteEvent(array $params)
    {
        if (empty($params['event_id']) || empty($params['calendar_id'])) {
            throw new \InvalidArgumentException('Missing required params.');
        }

        $postFields = [
            'event_id' => $params['event_id']
        ];

        try {
            return $this->connection->delete('/' . Cronofy::API_VERSION . '/calendars/' . $params['calendar_id'] . '/events', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    private function getConnectionUrl() : string
    {
        return $this->connection->getApiRootUrl() . '/' . Cronofy::API_VERSION;
    }
}