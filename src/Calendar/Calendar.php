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
            throw new CronofyException($e->getMessage(), $e->getCode());
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
        $postFields = $this->preparePostParams($params);
        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/calendars/' . $params['calendar_id'] . '/events', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    public function preparePostParams(array $params)
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

        return $postFields;
    }

    public function deleteEvent(array $params)
    {
        $postFields = $this->prepareDeleteParams($params);

        try {
            return $this->connection->delete('/' . Cronofy::API_VERSION . '/calendars/' . $params['calendar_id'] . '/events', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    public function prepareDeleteParams(array $params)
    {
        if (empty($params['event_id']) || empty($params['calendar_id'])) {
            throw new \InvalidArgumentException('Missing required params.');
        }

        $postFields = [
            'event_id' => $params['event_id']
        ];

        return $postFields;
    }

    public function createCalendar(array $params)
    {
        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/calendars', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    public function addToCalendar(array $params)
    {
        $postFields = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'oauth' => $params['oauth'],
            'event' => $params['event'],
        ];

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/add_to_calendar', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @throws CronofyException | \InvalidArgumentException
     */
    public function changeParticipationStatus(array $params)
    {
        if (empty($params['calendar_id']) || empty($params['event_uid']) || empty($params['status'])) {
            throw new \InvalidArgumentException('Missing required params.');
        }

        $postFields = [
            'status' => $params['status']
        ];

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/calendars/' . $params['calendar_id'] . '/events' . $params['event_uid'] . '/participation_status', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException | \InvalidArgumentException
     */
    public function availability(array $params)
    {
        if (empty($params['participants']) || empty($params['required_duration']) || empty($params['available_periods'])) {
            throw new \InvalidArgumentException('Missing required params.');
        }

        $postFields = array(
            'participants' => $params['participants'],
            'required_duration' => $params['required_duration'],
            'available_periods' => $params['available_periods']
        );

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/availability', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CronofyException
     */
    public function realTimeScheduling(array $params)
    {
        $postFields = array(
            'client_id' => $this->connection->getClientId(),
            'client_secret' => $this->connection->getClientSecret(),
            'oauth' => $params['oauth'],
            'event' => $params['event'],
            'availability' => $params['availability'],
            'target_calendars' => $params['target_calendars'],
            'tzid' => $params['tzid'],
        );

        try {
            return $this->connection->post('/' . Cronofy::API_VERSION . '/real_time_scheduling', $postFields);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    private function getConnectionUrl() : string
    {
        return $this->connection->getApiRootUrl() . '/' . Cronofy::API_VERSION;
    }
}