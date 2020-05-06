<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 08.07.2018
 * Time: 22:15
 */

namespace Statistics;


class NormalizationSettings implements InterfaceNormalizationSettings
{
    const XLSX = 'xlsx';
    const CSV = 'csv';
    const DISPLAY_DEFAULT = 'month';
    const ORIENTATION_DEFAULT = 'vertical';
    const COMPANY_FILTER_FOR_MODER = 2;
    const ROLE_MODER = 'MODER';
    private $display_array = ['day', 'week', 'week_day', 'month', 'total_only'];
    private $orientation_array = ['vertical', 'horizontal'];
    private $view_array = ['detail', 'connections', 'general', 'devices', 'vmr'];
    private $normal_settings = [];

    public function __construct($settings, $lang_dictionary, $constants)
    {
        $this->normal_settings['format_out_data'] = $constants['SETUP_FORMAT_STAT_OUT_DATA'];
        $this->normal_settings['dictionary'] = $lang_dictionary;
        $this->normal_settings['client_time_zone'] = $constants['CLIENT_TIME_ZONE'];
        $this->normal_settings['recording_camera_name'] = $constants['RECORDING_CAMERA_NAME'];
        $this->normal_settings['user_role'] = $constants['USER_ROLE'];
        $this->normal_settings['user_id'] = $constants['USER_ID'];
        $this->normal_settings['company_id'] = $constants['COMPANY_ID'];
        $this->orientation($settings['orientation']);
        $this->display($settings['display']);
        $this->range($settings['range']);
        $this->daysOfWeek();
        $this->meetingDurationParticipant($settings['m'], $settings['d'],$settings['p']);
        isset($settings['file']) ? $this->file($settings['file']) : false;
        isset($settings['company']) ? $this->company($settings['company']) : false;
        isset($settings['company_filter']) ? $this->company_filter($settings['company_filter']) : false;
        isset($settings['view']) ? $this->view($settings['view']) : false;
        isset($settings['show_users']) ? $this->showUsers($settings['show_users']) : false;
        isset($settings['show_terminals']) ? $this->showTerminals($settings['show_terminals']) : false;
        isset($settings['show_vmr']) ? $this->showVmr($settings['show_vmr']) : false;
        isset($settings['show_detailed']) ? $this->showDetailed($settings['show_detailed']) : false;
        isset($settings['planned']) ? $this->planned($settings['planned']) : false;
        isset($settings['no_total']) ? $this->noTotal($settings['no_total']) : false;

        $this->formatDuration();
        $this->nameKey();
    }

    public function setSettings($id, $name)
    {
        $this->normal_settings[$id] = $name;
    }

    public function getSettings($name)
    {
        if (array_key_exists($name, $this->normal_settings))
            return $this->normal_settings[$name];
        else
            return false;
    }

    public function getSettingsAll()
    {
        return $this->normal_settings;
    }

    public function addConfigData(array $configs)
    {
        foreach ($configs as $config) {
            if ('meeting_id_start' === $config['param'])
                $this->normal_settings['meeting_id_start'] = $config['value'];
            if ('meeting_id_finish' === $config['param'])
                $this->normal_settings['meeting_id_finish'] = $config['value'];
            if ('meeting_id_delta' === $config['param'])
                $this->normal_settings['meeting_id_delta'] = $config['value'];
        }
    }

    private function orientation($orientation)
    {
        $this->normal_settings['orientation'] = in_array($orientation,$this->orientation_array) ? $orientation : self::ORIENTATION_DEFAULT;
    }

    private function display($display)
    {
        $this->normal_settings['display'] = in_array($display,$this->display_array) ? $display : self::DISPLAY_DEFAULT;
    }

    private function range($range)
    {
        $rangeArray = explode(" ", $range);
        if (isset($rangeArray[0]) && strtotime($rangeArray[0])){
            $this->normal_settings['start_date'] = date('Y-m-d 00:00:00', strtotime($rangeArray[0]));
        } else {
            $this->normal_settings['start_date'] = date('Y-m-d 00:00:00',strtotime('first day of last month',time()));
        }
        if (isset($rangeArray[1]) && strtotime($rangeArray[1])){
            $this->normal_settings['finish_date'] = date('Y-m-d 23:59:59', strtotime($rangeArray[1]));
        } else {
            $this->normal_settings['finish_date'] = date('Y-m-d 23:59:59',strtotime('last day of last month',time()));
        }
        if (0 > (strtotime($this->normal_settings['finish_date']) - strtotime($this->normal_settings['start_date']))){
            $finish_date = $this->normal_settings['start_date'];
            $this->normal_settings['start_date'] = $this->normal_settings['finish_date'];
            $this->normal_settings['finish_date'] = $finish_date;
        }
    }

    private function daysOfWeek()
    {
        $this->normal_settings['name_day'] = [
            0 => $this->normal_settings['dictionary']['stat_day_0'],
            1 => $this->normal_settings['dictionary']['stat_day_1'],
            2 => $this->normal_settings['dictionary']['stat_day_2'],
            3 => $this->normal_settings['dictionary']['stat_day_3'],
            4 => $this->normal_settings['dictionary']['stat_day_4'],
            5 => $this->normal_settings['dictionary']['stat_day_5'],
            6 => $this->normal_settings['dictionary']['stat_day_6']
        ];
    }

    private function meetingDurationParticipant($meeting, $duration, $participant)
    {
        $this->normal_settings['meeting']  = (int)$meeting;
        $this->normal_settings['duration'] = (int)$duration;
        $this->normal_settings['participant'] = (int)$participant;
        if (0 === $this->normal_settings['duration'] && 0 === $this->normal_settings['participant']) {
            $this->normal_settings['meeting'] = 1;
        }
    }

    private function file($file)
    {
        if (self::XLSX === $file)
            $this->normal_settings['file'] = 'xlsx';
        if (self::CSV === $file)
            $this->normal_settings['file'] = 'csv';
    }

    private function company($company)
    {
        if (self::ROLE_MODER === $this->normal_settings['user_role']) {
            $this->normal_settings['company'] = (int)$this->normal_settings['company_id'];
        } else {
            $this->normal_settings['company'] = (int)$company;
        }
    }
    private function company_filter($company_filter)
    {
        if (self::ROLE_MODER === $this->normal_settings['user_role']) {
            $this->normal_settings['company_filter'] = self::COMPANY_FILTER_FOR_MODER;
        } else {
            $this->normal_settings['company_filter'] = (int)$company_filter;
        }
    }

    private function view($view)
    {
        $this->normal_settings['view'] = in_array($view,$this->view_array) ? $view : '';
    }

    private function showUsers($show_users)
    {
        $this->normal_settings['show_users']  = (int)$show_users;
    }

    private function showTerminals($show_terminals)
    {
        $this->normal_settings['show_terminals']  = (int)$show_terminals;
    }

    private function showVmr($show_vmr)
    {
        $this->normal_settings['show_vmr']  = (int)$show_vmr;
    }

    private function showDetailed($show_detailed)
    {
        if (1 === (int)$show_detailed){
            $this->normal_settings['show_detailed'] = (int)$show_detailed;
            $this->normal_settings['show_users'] = 0;
            $this->normal_settings['show_terminals'] = 0;
        } else{
            $this->normal_settings['show_detailed'] = 0;
        }
    }

    private function planned($planned)
    {
        $this->normal_settings['planned'] = (int)$planned;
    }

    private function noTotal($no_total)
    {
        $this->normal_settings['no_total'] = (int)$no_total;
    }

    private function formatDuration()
    {
        if ( 1 === $this->getSettings('format_out_data'))
            $this->normal_settings['format_duration'] = ' (hh:mm)';
        else
            $this->normal_settings['format_duration'] = '';
    }

    private function nameKey()
    {
        $this->normal_settings['columns'] = 'columns';
        $this->normal_settings['columnsAudio'] = 'columnsAudio';
        $this->normal_settings['dataSource'] = 'dataSource';
        $this->normal_settings['dataSourceAudio'] = 'dataSourceAudio';
        $this->normal_settings['dataSourceVideo'] = 'dataSourceVideo';
        $this->normal_settings['dataSourceWeb'] = 'dataSourceWeb';
        $this->normal_settings['columnsCompanies'] = 'columnsCompanies';
        $this->normal_settings['dataSourceCompanies'] = 'dataSourceCompanies';
        $this->normal_settings['date'] = 'date';
        $this->normal_settings['chartTotals'] = 'chartTotals';
        $this->normal_settings['chartLabels'] = 'chartLabels';
        $this->normal_settings['chartMeetingsData'] = 'chartMeetingsData';
        $this->normal_settings['chartParticipantsData'] = 'chartParticipantsData';
        $this->normal_settings['chartDurationData'] = 'chartDurationData';
        $this->normal_settings['device'] = 'device';
        $this->normal_settings['m'] = 'm';
        $this->normal_settings['d'] = 'd';
        $this->normal_settings['p'] = 'p';
        $this->normal_settings['total_only'] = 'total_only';
        $this->normal_settings['total_to_users'] = 'Total to users';
        $this->normal_settings['total_to_terminals'] = 'Total to terminals';
        $this->normal_settings['total'] = 'Total';
        $this->normal_settings['category'] = 'category';
        $this->normal_settings['dataRange'] = 'dataRange';
        $this->normal_settings['label_name'] = 'label';
        $this->normal_settings['data_name'] = 'data';
        $this->normal_settings['person'] = 'person';
        $this->normal_settings['end_point'] = 'end_point';
        $this->normal_settings['alias_name'] = 'Alias Name';
        $this->normal_settings['alias_number'] = 'Alias Number';
    }



}