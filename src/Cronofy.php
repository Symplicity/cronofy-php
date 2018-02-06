<?php

namespace Cronofy;

use Cronofy\Calendar\Calendar;
use Cronofy\Http\Connection;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\Interfaces\TokenInterface;

/**
 * Class Cronofy
 * @property TokenInterface $tokenManager
 * @property ConnectionInterface $connection
 * @package Cronofy
 */
class Cronofy
{

    public const USERAGENT = 'Cronofy PHP 0.15.0';
    public const API_VERSION = 'v1';

    protected $config = [];

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    public function __get($property)
    {
        if ($property === 'tokenManager') {
            $this->tokenManager = (($tokenManager = $this->getConfigForKey('tokenManager')) && $tokenManager instanceof TokenInterface) ? $tokenManager : new Token($this->connection);
            return $this->tokenManager;
        } elseif ($property === 'connection') {
            $this->connection = new Connection($this->config);
            return $this->connection;
        }
    }

    public function request_token(array $params = []) : array
    {
        $response = [];
        try {
            $tokenFound = $this->tokenManager->request($params);
            if ($tokenFound) {
                $response =  [
                    'access_token' => $this->tokenManager->getAccessToken(),
                    'refresh_token' => $this->tokenManager->getRefreshToken()
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'error_message' => $e->getMessage()
            ];
        }
        return $response;
    }

    public function refresh_token() : array
    {
        $response = [];
        try {
            $tokenFound = $this->tokenManager->refresh();
            if ($tokenFound) {
                $response =  [
                    'access_token' => $this->tokenManager->getAccessToken(),
                    'refresh_token' => $this->tokenManager->getRefreshToken()
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'error_message' => $e->getMessage()
            ];
        }
        return $response;
    }

    public function request_link_token()
    {
        try {
            return $this->tokenManager->requestLinkToken();
        } catch (\Exception $e) {

        }
    }

    public function revoke_authorization($token)
    {
        try {
            return $this->tokenManager->revoke($token);
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * @param array $params An array of additional parameters
        redirect_uri : String The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
        scope : An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
        state : String A value that will be returned to you unaltered along with the user's authorization request decision. OPTIONAL
        avoid_linking : Boolean when true means we will avoid linking calendar accounts together under one set of credentials. OPTIONAL
        link_token : String The link token to explicitly link to a pre-existing account. OPTIONAL
     * @return string The URL to authorize your access to the Cronofy API
     */
    public function getAuthorizationURL(array $params) : string
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

    /**
     *
     * @param array $params: An array of additional parameters
            redirect_uri : String. The HTTP or HTTPS URI you wish the user's authorization request decision to be redirected to. REQUIRED
            scope : Array. An array of scopes to be granted by the access token. Possible scopes detailed in the Cronofy API documentation. REQUIRED
            delegated_scope : Array. An array of scopes to be granted that will be allowed to be granted to the account's users. REQUIRED
            state : String. A value that will be returned to you unaltered along with the user's authorization request decsion. OPTIONAL
     * @return string The URL to authorize your enterprise connect access to the Cronofy API
     */
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

    public function list_calendar()
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->listCalendars();
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
     * @return ResponseIterator
     */
    public function read_events(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->readEvents($params);
    }

    /**
     * calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
     * String event_id : The String that uniquely identifies the event. REQUIRED
     * @param $params
     * @return bool
     */
    public function delete_event(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->deleteEvent($params);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getConfigForKey(string $key): ?string
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
        return null;
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    private function setConfig(array $config = [])
    {
        $this->config = $config;
    }
}