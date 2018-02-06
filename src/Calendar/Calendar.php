<?php

namespace Cronofy\Calendar;

use Cronofy\Cronofy;
use Cronofy\Exception\CronofyException;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\ResponseIterator;

final class Calendar
{
    private $connection;
    private $responseIterator;

    public function __construct(ConnectionInterface $connection, ResponseIterator $responseIterator)
    {
        $this->connection = $connection;
        $this->responseIterator = $responseIterator;
    }

    public function listCalendars()
    {
        try {
            return $this->connection->get(Cronofy::API_VERSION . '/calendars');
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     *  Params may include :
     *   Date from : The minimum date from which to return events. Defaults to 16 days in the past. OPTIONAL
     *   Date to : The date to return events up until. Defaults to 201 days in the future. OPTIONAL
     *   String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
     *   Boolean include_deleted : Indicates whether to include or exclude events that have been deleted. Defaults to excluding deleted events. OPTIONAL
     *   Boolean include_moved: Indicates whether events that have ever existed within the given window should be included or excluded from the results. Defaults to only include events currently within the search window. OPTIONAL
     *   Time last_modified : The Time that events must be modified on or after in order to be returned. Defaults to including all events regardless of when they were last modified. OPTIONAL
     *   Boolean include_managed : Indiciates whether events that you are managing for the account should be included or excluded from the results. Defaults to include only non-managed events. OPTIONAL
     *   Boolean only_managed : Indicates whether only events that you are managing for the account should be included in the results. OPTIONAL
     *   Array calendar_ids : Restricts the returned events to those within the set of specified calendar_ids. Defaults to returning events from all of a user's calendars. OPTIONAL
     *   Boolean localized_times : Indicates whether the events should have their start and end times returned with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
     *   Boolean include_geo : Indicates whether the events should have their location's latitude and longitude returned where available. OPTIONAL
     * @param array $params
     * @returns ResponseIterator
     * @throws CronofyException
     */
    public function readEvents(array $params = []) : ResponseIterator
    {
        try {
            $url = $this->getConnectionUrl() . '/events';
            return $this->responseIterator->setItems($url, 'events', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * Params options:
     * Date from : The minimum date from which to return free-busy information. Defaults to 16 days in the past. OPTIONAL
     * Date to : The date to return free-busy information up until. Defaults to 201 days in the future. OPTIONAL
     * String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
     * Boolean include_managed : Indiciates whether events that you are managing for the account should be included or excluded from the results. Defaults to include only non-managed events. OPTIONAL
     * Array calendar_ids : Restricts the returned free-busy information to those within the set of specified calendar_ids. Defaults to returning free-busy information from all of a user's calendars. OPTIONAL
     * Boolean localized_times : Indicates whether the free-busy information should have their start and end times returned with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
     * @param array $params
     * @return ResponseIterator
     * @throws CronofyException
     */
    public function freeBusy(array $params) : ResponseIterator
    {
        try {
            $url = $this->getConnectionUrl() . '/free_busy';
            return $this->responseIterator->setItems($url, 'free_busy', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }

    /**
     * Param options:
     * calendar_id : The calendar_id of the calendar you wish the event to be added to. REQUIRED
     * String event_id : The String that uniquely identifies the event. REQUIRED
     * String summary : The String to use as the summary, sometimes referred to as the name, of the event. REQUIRED
     * String description : The String to use as the description, sometimes referred to as the notes, of the event. REQUIRED
     * String tzid : A String representing a known time zone identifier from the IANA Time Zone Database. OPTIONAL
     * Time start: The start time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * Time end: The end time can be provided as a simple Time string or an object with two attributes, time and tzid. REQUIRED
     * String location.description : The String describing the event's location. OPTIONAL
     * String location.lat : The String describing the event's latitude. OPTIONAL
     * String location.long : The String describing the event's longitude. OPTIONAL
     * Array reminders : An array of arrays detailing a length of time and a quantity. OPTIONAL for example: array(array("minutes" => 30), array("minutes" => 1440)
     * Boolean reminders_create_only: A Boolean specifying whether reminders should only be applied when creating an event. OPTIONAL
     * String transparency : The transparency of the event. Accepted values are "transparent" and "opaque". OPTIONAL
     * Array attendees : An array of "invite" and "reject" arrays which are lists of attendees to invite and remove from the event. OPTIONAL for example: array("invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee")) "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee")))
     *
     * @param array $params
     * @return bool
     * @throws CronofyException
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