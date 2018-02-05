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
     * @param array $params
        Date from : The minimum date from which to return events. Defaults to 16 days in the past. OPTIONAL
        Date to : The date to return events up until. Defaults to 201 days in the future. OPTIONAL
        String tzid : A string representing a known time zone identifier from the IANA Time Zone Database. REQUIRED
        Boolean include_deleted : Indicates whether to include or exclude events that have been deleted. Defaults to excluding deleted events. OPTIONAL
        Boolean include_moved: Indicates whether events that have ever existed within the given window should be included or excluded from the results. Defaults to only include events currently within the search window. OPTIONAL
        Time last_modified : The Time that events must be modified on or after in order to be returned. Defaults to including all events regardless of when they were last modified. OPTIONAL
        Boolean include_managed : Indiciates whether events that you are managing for the account should be included or excluded from the results. Defaults to include only non-managed events. OPTIONAL
        Boolean only_managed : Indicates whether only events that you are managing for the account should be included in the results. OPTIONAL
        Array calendar_ids : Restricts the returned events to those within the set of specified calendar_ids. Defaults to returning events from all of a user's calendars. OPTIONAL
        Boolean localized_times : Indicates whether the events should have their start and end times returned with any available localization information. Defaults to returning start and end times as simple Time values. OPTIONAL
        Boolean include_geo : Indicates whether the events should have their location's latitude and longitude returned where available. OPTIONAL
     * @returns ResponseIterator
     * @throws CronofyException
     */
    public function readEvents(array $params = []) : ResponseIterator
    {
        try {
            $url = $this->connection->getApiRootUrl . '/' . Cronofy::API_VERSION . '/events';
            return $this->responseIterator->setItems($url, 'events', $params);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage());
        }
    }
}