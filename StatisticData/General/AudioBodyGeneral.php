<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 07.08.2018
 * Time: 16:12
 */

namespace Statistics\StatisticData\General;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;

class AudioBodyGeneral implements InterfaceBody
{
    private $body = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createBody(array $data)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');
        foreach ($data as $row) {
            $num_day_week = $row['meeting_stat_day'];
            if ('week_day' === $this->settings->getSettings('display') )
                $num_day_week = $this->settings->getSettings('name_day')[$row['meeting_stat_day']];
            $this->body[$row['meeting_stat_day']][$dictionary['stat_date']] = Converter::weekString($num_day_week, $row['start_date'], $this->settings);
            if ($this->isEmpty($row['meeting_stat_day'])) {
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_meetings'], 0);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_participants'], 0);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_duration'] . $format_duration, 0);
            }
            $this->addBody($row['meeting_stat_day'], $dictionary['stat_meetings'], 1);
            $this->addBody($row['meeting_stat_day'], $dictionary['stat_participants'], $row['num_users_meetings']);
            $this->addBody($row['meeting_stat_day'], $dictionary['stat_duration'] . $format_duration, Converter::convertToMinute($this->audioDuration($row['finish_date'], $row['start_date'])));
        }
        $this->addTotal();
    }

    public function addTotal()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');

        $total[$dictionary['stat_date']] = $dictionary['stat_total'];
        $total[$dictionary['stat_meetings']] = 0;
        $total[$dictionary['stat_participants']] = 0;
        $total[$dictionary['stat_duration'] . $format_duration] = 0;
        foreach ($this->body as $row) {
            $total[$dictionary['stat_meetings']] += $row[$dictionary['stat_meetings']];
            $total[$dictionary['stat_participants']] += $row[$dictionary['stat_participants']];
            $total[$dictionary['stat_duration'] . $format_duration] += $row[$dictionary['stat_duration']];
        }
        $this->body[$dictionary['stat_total']] = $total;
    }

    public function converterBody()
    {
        $this->body = Converter::convertToAudioGeneral($this, $this->settings);
        $this->body = array_values($this->body);
    }

    public function getBody()
    {
        return $this->body;
    }

    public function isEmpty($meeting_stat_day)
    {
        return  is_null($this->body[$meeting_stat_day]) ? false : true;
    }
    
    private function addBody($format_date, $type, $data)
    {
        if (isset($this->body[$format_date][$type])) {
            $this->body[$format_date][$type] += $data;
        }
        else {
            $this->body[$format_date][$type] = $data;
        }
    }

    private function audioDuration($start, $finish)
    {
        return floor((strtotime($finish) - strtotime($start))/60);
    }
}