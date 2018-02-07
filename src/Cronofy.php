<?php

namespace Cronofy;

use Cronofy\Calendar\Calendar;
use Cronofy\Calendar\Channel;
use Cronofy\Calendar\Channels;
use Cronofy\Calendar\Profiles;
use Cronofy\Calendar\SmartInvite;
use Cronofy\Exception\CronofyException;
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

    public function list_calendar() : array
    {
        try {
            $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
            return $calendars->listCalendars();
        } catch (CronofyException $e) {
            return ['error' => $e->getMessage()];
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
     * @return ResponseIterator
     */
    public function read_events(array $params)
    {
        try {
            $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
            return $calendars->readEvents($params);
        } catch (CronofyException $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
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
    public function free_busy(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->freeBusy($params);
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
     * @throws CronofyException | \InvalidArgumentException
     */
    public function upsert_event(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->upsertEvent($params);
    }

    /**
     * Params need to include:
     * String callback_url : The URL that is notified whenever a change is made. REQUIRED
     * @param array $params
     * @return result of new channel
     * @throws CronofyException | \InvalidArgumentException
     */
    public function create_channel(array $params)
    {
        $channel = new Channel($this->connection);
        return $channel->createChannel($params);
    }

    public function list_channels()
    {
        $channel = new Channel($this->connection);
        return $channel->listChannels();
    }

    /**
     * Params must include:
     * channel_id : The ID of the channel to be closed. REQUIRED
     * @param array $params
     * @return array - Array of channels
     * @throws CronofyException | \InvalidArgumentException
     */
    public function close_channel(array $params)
    {
        $channel = new Channel($this->connection);
        return $channel->closeChannel($params);
    }

    /**
     * Info for the user logged in. Details are available in the Cronofy API Documentation
     * @return mixed
     */
    public function get_account()
    {
        $profiles = new Profiles($this->connection);
        return $profiles->getAccount();
    }

    /**
     * Userinfo for the user logged in. Details are available in the Cronofy API Documentation
     * @return mixed
     */
    public function get_userinfo()
    {
        $profiles = new Profiles($this->connection);
        return $profiles->getUserInfo();
    }

    /**
     * list of all the authenticated user's calendar profiles. Details are available in the Cronofy API Documentation
     * @return mixed
     */
    public function get_profiles()
    {
        $profiles = new Profiles($this->connection);
        return $profiles->getProfiles();
    }

    /**
     * Params may include:
     * calendar_id : The calendar_id of the calendar you wish the event to be removed from. REQUIRED
     * event_uid : The String that uniquely identifies the event. REQUIRED
     * @param array $params
     */
    public function delete_external_event(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->deleteEvent($params);
    }

    /**
     * Params should include :
     * permissions : The permissions to elevate to. Should be in an array of `array($calendar_id, $permission_level)`. REQUIRED
     * redirect_uri : The application's redirect URI. REQUIRED
     *
     * @param $params
     * @return mixed
     * @throws CronofyException
     */
    public function elevated_permissions(array $params)
    {
        $user = new User($this->connection);
        return $user->elevatePermissions($params);
    }

    /*
         email : The email of the user to be authorized. REQUIRED
         scope : The scopes to authorize for the user. REQUIRED
         callback_url : The URL to return to after authorization. REQUIRED
        */
    public function authorize_with_service_account(array $params)
    {
        $user = new User($this->connection);
        return $user->authorizeWithServiceAccount($params);
    }

    /**
     * oauth: An object of redirect_uri and scope following the event creation, for example: array("redirect_uri" => "http://test.com/","scope" => "test_scope")
     * event: An object with an event's details, for example: array("event_id" => "test_event_id", "summary" => "Add to Calendar test event", "start" => "2017-01-01T12:00:00Z", "end" => "2017-01-01T15:00:00Z")
     * @param array $params
     */
    public function add_to_calendar(array $params)
    {
        $calendars = new Calendar($this->connection, new ResponseIterator($this->connection));
        return $calendars->addToCalendar($params);
    }

    /*
          returns $result - Array of resources. Details
          are available in the Cronofy API Documentation
         */
    public function resources()
    {
        try {
            return $this->connection->get('/' . self::API_VERSION . '/resources');
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /*
          participants : An array of the groups of participants whose availability should be taken into account. REQUIRED
                         for example: array(
                                        array("members" => array(
                                          array("sub" => "acc_567236000909002"),
                                          array("sub" => "acc_678347111010113")
                                        ), "required" => "all")
                                      )
          required_duration : Duration that an available period must last to be considered viable. REQUIRED
                         for example: array("minutes" => 60)
          available_periods : An array of available periods within which suitable matches may be found. REQUIRED
                         for example: array(
                                        array("start" => "2017-01-01T09:00:00Z", "end" => "2017-01-01T18:00:00Z"),
                                        array("start" => "2017-01-02T09:00:00Z", "end" => "2017-01-02T18:00:00Z")
                                      )
         */
    public function availability(array $params)
    {
        $calendars = new Calendar($this->connection);
        return $calendars->availability($params);
    }

    /*
          oauth: An object of redirect_uri and scope following the event creation
                 for example: array(
                                "redirect_uri" => "http://test.com/",
                                "scope" => "test_scope"
                              )
          event: An object with an event's details
                 for example: array(
                                "event_id" => "test_event_id",
                                "summary" => "Add to Calendar test event",
                              )
          availability: An object holding the event's availability information
                 for example: array(
                                "participants" => array(
                                  array(
                                    "members" => array(
                                      array(
                                        "sub" => "acc_567236000909002"
                                        "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
                                      )
                                    ),
                                    "required" => "all"
                                  )
                                ),
                                "required_duration" => array(
                                  "minutes" => 60
                                ),
                                "available_periods" => array(
                                  array(
                                    "start" => "2017-01-01T09:00:00Z",
                                    "end" => "2017-01-01T17:00:00Z"
                                  )
                                )
                              )
          target_calendars: An object holding the calendars for the event to be inserted into
                  for example: array(
                    array(
                      "sub" => "acc_567236000909002",
                      "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
                    )
                  )
          tzid: the timezone to create the event in
                for example:  'Europe/London'
         */
    public function real_time_scheduling($params)
    {
        $calendar = new Calendar($this->connection);
        return $calendar->scheduleRealTime($params);
    }


    /*
          Array event: An object with an event's details REQUIRED
                 for example: array(
                                "summary" => "Add to Calendar test event",
                                "start" => "2017-01-01T12:00:00Z",
                                "end" => "2017-01-01T15:00:00Z"
                              )
          Array recipient: An object with recipient details REQUIRED
                     for example: array(
                         "email" => "example@example.com"
                     )
          String smart_invite_id: A string representing the id for the smart invite. REQUIRED
          String callback_url : The URL that is notified whenever a change is made. REQUIRED
         */
    public function create_smart_invite(array $params)
    {
        $smartInvite = new SmartInvite($this->connection);
        return $smartInvite->createSmartInvite($params);
    }

    /*
         Array recipient: An object with recipient details REQUIRED
                    for example: array(
                        "email" => "example@example.com"
                    )
         String smart_invite_id: A string representing the id for the smart invite. REQUIRED
        */
    public function cancel_smart_invite($params)
    {
        $smartInvite = new SmartInvite($this->connection);
        return $smartInvite->cancelSmartInvite($params);
    }

    /*
          String smart_invite_id: A string representing the id for the smart invite. REQUIRED
          String recipient_email: A string representing the email of the recipient to get status for. REQUIRED
         */
    public function get_smart_invite($smart_invite_id, $recipient_email)
    {
        $smartInvite = new SmartInvite($this->connection);
        return $smartInvite->getSmartInvite($smart_invite_id, $recipient_email);
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
