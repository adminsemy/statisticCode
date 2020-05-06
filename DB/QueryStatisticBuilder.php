<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 08.07.2018
 * Time: 21:58
 */

namespace Statistics\DB;


use Statistics\NormalizationSettings;

class QueryStatisticBuilder extends DBQueriesMySQL
{
    private $settings;
    private $type_date;
    private $planned_request;
    private $meeting_planned_request;
    private $recording_camera_name = '';
    private $company = '';
    private $meeting_company = '';
    private $company_not_zero = '';
    private $meeting_company_not_zero = '';
    private $statist_for_user = '';
    private $order = 'meeting_stat_day';
    private $meeting_order = 'start_date';

    public function __construct(NormalizationSettings $settings)
    {
        $this->settings = $settings;
        $this->typeDate($this->settings->getSettings('display'));
        $this->setParametres();
        $this->setQueries();
    }

    private function setQueries()
    {
        $client_time_zone = $this->settings->getSettings('client_time_zone');
        $start_date = $this->settings->getSettings('start_date');
        $finish_date = $this->settings->getSettings('finish_date');
        $query_general = "SELECT
						(stat_pexip.stat_user_id) as stat_user_id,
						MAX(stat_pexip.stat_user_fname) as stat_user_fname,
						MAX(stat_pexip.stat_user_lname) as stat_user_lname,
						MAX(stat_pexip.stat_user_company_id) as stat_user_company_id,
						MAX(stat_pexip.stat_host_company_id) as stat_host_company_id,
						MAX(stat_pexip.stat_user_type) as stat_user_type,
						DATE_FORMAT(DATE_ADD(stat_date, INTERVAL " . $client_time_zone . " HOUR),'" . $this->type_date . "') as meeting_stat_day,
						MAX(stat_pexip.stat_date) as stat_date,
						MAX(DATE_FORMAT(DATE_ADD(stat_date, INTERVAL " . $client_time_zone . " HOUR),'%j')) as day,
						SUM(stat_participant_duration) as sum_duration,
						SUM(stat_number_participants_at_meeting) as sum_participants,
						COUNT(stat_meeting_id) as num_meetings				
					FROM stat_pexip				
					WHERE
						stat_date BETWEEN
							DATE_SUB('" . $start_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
							DATE_SUB('" . $finish_date . "', INTERVAL " . $client_time_zone . " HOUR) AND					
						stat_participant_name NOT RLIKE ':[0-9]{1,}'
						$this->planned_request
						$this->recording_camera_name
						$this->company
						$this->company_not_zero
						$this->statist_for_user
	
					GROUP BY meeting_stat_day, stat_user_id
					ORDER BY " . $this->order . "
		";
        $query_total = "SELECT
						DATE_FORMAT(DATE_ADD(stat_date, INTERVAL " . $client_time_zone . " HOUR),'" . $this->type_date . "') as meeting_stat_day,
						MAX(stat_pexip.stat_date) as stat_date,
						MAX(stat_pexip.stat_meeting_duration) as stat_meeting_duration,
						MAX(stat_pexip.stat_host_company_id) as stat_host_company_id,
						MAX(stat_pexip.stat_host_company_name) as stat_host_company_name,
						MAX(stat_participant_duration) as sum_duration,
						MAX(stat_number_participants_at_meeting) as sum_participants,
						MAX(stat_participant_room) as stat_participant_room,				
						MAX(meetings.meeting_video) as meeting_video				
					FROM stat_pexip
					JOIN meetings ON stat_pexip.stat_meeting_id = meetings.meeting_id
					WHERE
						stat_date BETWEEN
							DATE_SUB('" . $start_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
							DATE_SUB('" . $finish_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
						stat_meeting_id > 0 AND 
						stat_participant_name NOT RLIKE ':[0-9]{1,}' 
						$this->planned_request
						$this->recording_camera_name
						$this->company
						$this->company_not_zero
						$this->statist_for_user
					GROUP BY meeting_stat_day, stat_date, stat_meeting_id
					ORDER BY " . $this->order . "
		";
        $query_company_total = "SELECT
						DATE_FORMAT(DATE_ADD(stat_date, INTERVAL " . $client_time_zone . " HOUR),'" . $this->type_date . "') as meeting_stat_day,
						max(stat_pexip.stat_date) as stat_date,
						max(stat_pexip.stat_meeting_duration) as stat_meeting_duration,
						
						max(stat_pexip.stat_user_company_id) as stat_user_company_id,
						max(stat_pexip.stat_user_company_name) as stat_user_company_name,
						
						max(stat_pexip.stat_host_company_id) as stat_host_company_id,
						max(stat_pexip.stat_host_company_name) as stat_host_company_name,
												
						MAX(stat_participant_duration) as sum_duration,
						MAX(stat_number_participants_at_meeting) as sum_participants			
					FROM stat_pexip
					WHERE
						stat_date BETWEEN
							DATE_SUB('" . $start_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
							DATE_SUB('" . $finish_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
						stat_meeting_id > 0 AND
						stat_host_company_name > ''
						$this->planned_request
						$this->recording_camera_name
						$this->company
						$this->company_not_zero
						$this->statist_for_user	
					GROUP BY meeting_stat_day, stat_date, stat_meeting_id
					ORDER BY stat_host_company_name
		";
        $query_connections = "SELECT
						stat_pexip.stat_user_id as stat_user_id,
						stat_pexip.stat_user_fname as stat_user_fname,
						stat_pexip.stat_user_lname as stat_user_lname,
						stat_pexip.stat_meeting_id as stat_meeting_id,
						stat_pexip.stat_user_h323 as stat_user_h323,
						stat_pexip.stat_user_sip as stat_user_sip,
						stat_pexip.stat_user_type as stat_user_type,
						stat_pexip.stat_participant_browser as stat_participant_browser,
						stat_pexip.stat_participant_os as stat_participant_os,
						stat_pexip.stat_participant_device as stat_participant_device,
						DATE_FORMAT(DATE_ADD(stat_date, INTERVAL " . $client_time_zone . " HOUR),'" . $this->type_date . "') as meeting_stat_day,
						stat_pexip.stat_date as stat_date
					FROM stat_pexip				
					WHERE
						stat_date BETWEEN
							DATE_SUB('" . $start_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
							DATE_SUB('" . $finish_date . "', INTERVAL " . $client_time_zone . " HOUR)					
						$this->planned_request
						$this->recording_camera_name
						$this->company
						$this->company_not_zero
						$this->statist_for_user	
					GROUP BY meeting_stat_day, stat_user_id, stat_user_fname, stat_user_lname, stat_meeting_id, stat_user_h323, stat_user_sip, stat_user_type, 
					stat_participant_browser, stat_participant_os, stat_participant_device, stat_date
					ORDER BY " . $this->order . "
		";
        $query_rooms = "SELECT
						meeting_standing_id,
						meeting_standing_name,
						meeting_standing_desc,
						meeting_standing_alias1,
						meeting_standing_alias2,
						meeting_standing_alias3,
						meeting_standing_alias4
					FROM meeting_standing_rooms
		";
        $query_user = "SELECT
                        user_id,
						user_fname,
						user_lname
					FROM users
					WHERE user_id = ". USER_ID ."
		";
        $query_config = "SELECT
                        param,
						value
					FROM configs";
        $query_audio_meetings = "SELECT
                        MAX(meetings.meeting_id) as meeting_id,
                        MAX(companies.company_name) as company_name,
                        MAX(meetings.meeting_company_id) as meeting_company_id,
						DATE_FORMAT(DATE_ADD(meetings.meeting_start_date, INTERVAL " . $client_time_zone . " HOUR),'" . $this->type_date . "') as meeting_stat_day,
						MAX(meetings.meeting_start_date) as start_date,
						MAX(meetings.meeting_finish_date) as finish_date,
						COUNT(meeting_users.user_id) as num_users_meetings
					FROM meetings
					LEFT JOIN meeting_users ON meetings.meeting_id = meeting_users.meeting_id
					RIGHT JOIN companies ON meetings.meeting_company_id = companies.company_id
					JOIN users ON meeting_users.user_id = users.user_id
					WHERE
						meeting_start_date BETWEEN
							DATE_SUB('" . $start_date . "', INTERVAL " . $client_time_zone . " HOUR) AND
							DATE_SUB('" . $finish_date . "', INTERVAL " . $client_time_zone . " HOUR) AND					
						meeting_video = 20
						$this->meeting_planned_request
						$this->meeting_company
					GROUP BY meeting_stat_day, meetings.meeting_id
					ORDER BY " . $this->meeting_order . "
		";

        $this->queryDB('general', $query_general);
        $this->queryDB('total', $query_total);
        $this->queryDB('company_total', $query_company_total);
        $this->queryDB('connections', $query_connections);
        $this->queryDB('rooms', $query_rooms);
        $this->queryDB('user', $query_user);
        $this->queryDB('config', $query_config);
        $this->queryDB('meetings_audio', $query_audio_meetings);
    }
    private function setParametres()
    {
        $this->setPlanned();
        $this->setRecordingCamera();
        $this->setCompany();
        $this->setOrder();
        $this->setCompanyNotZero();
        $this->setStatistForUser();
    }
    private function typeDate($display)
    {
        $format_date = '%d.%m.%Y';
        $format_week = '%u';
        $format_week_day = '%w';
        $format_month = '%m.%Y';
        if (1 === $this->settings->getSettings('format_out_data')){
            $format_date = '%Y-%m-%d';
            $format_month = '%Y-%m';
        }
        switch ($display) {
            case 'day':
                $this->type_date = $format_date;
                break;
            case 'week':
                $this->type_date = $format_week;
                break;
            case 'week_day':
                $this->type_date = $format_week_day;
                break;
            case 'month':
                $this->type_date = $format_month;
                break;
            default:
                $this->type_date = $format_month;
        }
    }

    private function setPlanned()
    {
        if (1 === $this->settings->getSettings('planned')) {
            $this->planned_request = ' AND stat_meeting_planned_type <> 1 ';
            $this->meeting_planned_request = ' AND meeting_planned_type <> 1 ';
        }
        if (2 === $this->settings->getSettings('planned')) {
            $this->planned_request = ' AND stat_meeting_planned_type = 1 ';
            $this->meeting_planned_request = ' AND meeting_planned_type = 1 ';
        }
        if (1 === $this->settings->getSettings('format_out_data')) {
            $this->planned_request = ' AND stat_meeting_planned_type <> 1 ';
            $this->meeting_planned_request = ' AND meeting_planned_type <> 1 ';
        }
    }

    private function setRecordingCamera()
    {
        if (!empty($this->settings->getSettings('recording_camera_name')))
            $this->recording_camera_name = " AND stat_participant_name NOT LIKE '" . $this->settings->getSettings('recording_camera_name') . "'";
    }

    private function setCompany()
    {
        if ($this->settings->getSettings('company') > 0) {
            $company_id = $this->settings->getSettings('company');
            $request = ' AND stat_host_company_id = ';
            $meeting_request = ' AND meeting_company_id = ';
            $this->company = $request . $company_id;
            $this->meeting_company = $meeting_request . $company_id;
            switch ($this->settings->getSettings('company_filter')) {
                case 0:
                    $request = ' AND (stat_user_company_id = ' . $company_id;
                    $request_two = ' OR stat_host_company_id = ' . $company_id . ')';
                    $meeting_request = ' AND (users.user_company_id = ' . $company_id;
                    $meeting_request_two = ' OR meetings.meeting_company_id = ' . $company_id . ')';
                    $this->company = $request . $request_two;
                    $this->meeting_company = $meeting_request . $meeting_request_two;
                    break;
                case 1:
                    $request = ' AND stat_user_company_id = ' . $company_id;
                    $meeting_request = ' AND users.user_company_id = ' . $company_id;
                    $this->company = $request;
                    $this->meeting_company = $meeting_request;
                    break;
                case 2:
                    $request = ' AND stat_host_company_id = ' . $company_id;
                    $this->company = $request;
                    break;
            }
        }
    }

    private function setOrder()
    {
        if ('week_day' === $this->settings->getSettings('display')) {
            $this->order = 'FIELD(meeting_stat_day, 1, 2, 3, 4, 5, 6, 7, 0)';
            $this->meeting_order = 'FIELD(meeting_stat_day, 1, 2, 3, 4, 5, 6, 7, 0)';
        }
        if ('day' === $this->settings->getSettings('display')) {
            $this->order = 'stat_date';
        }
    }

    private function setCompanyNotZero()
    {
        if (1 === $this->settings->getSettings('format_out_data')) {
            $this->company_not_zero = ' AND stat_host_company_id > 0';
            $this->meeting_company_not_zero = ' AND meeting_company_id > 0';
        }
    }

    private function setStatistForUser()
    {
        if (2 === $this->settings->getSettings('format_out_data') && 'USER' === $this->settings->getSettings('user_role')) {
            $this->statist_for_user = ' AND (stat_user_id = ' . $this->settings->getSettings('user_id') . ' OR stat_host_user_id = ' . $this->settings->getSettings('user_id') . ') ';
        }
    }
}