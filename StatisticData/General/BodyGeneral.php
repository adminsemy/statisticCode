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
use Statistics\StatisticData\InterfaceTotal;

class BodyGeneral implements InterfaceBody
{
    private $body = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createBody(array $data, $meeting_video = null)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');
        foreach ($data as $row) {
            $num_day_week = $row['meeting_stat_day'];
            if ('week_day' === $this->settings->getSettings('display') )
                $num_day_week = $this->settings->getSettings('name_day')[$row['meeting_stat_day']];
            $this->body[$row['meeting_stat_day']][$dictionary['stat_date']] = Converter::weekString($num_day_week, $row['stat_date'], $this->settings);
            if ($this->isEmpty($row['meeting_stat_day'])) {
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_meetings'], 0);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_participants'], 0);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_duration'] . $format_duration, 0);
            }
            $row_meeting_video = (int)$row['meeting_video'];
            if (is_null($meeting_video) ? true : $row_meeting_video === $meeting_video) {
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_meetings'], 1);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_participants'], $row['sum_participants']);
                $this->addBody($row['meeting_stat_day'], $dictionary['stat_duration'] . $format_duration, Converter::convertToMinute($row['stat_meeting_duration']));
            }
        }
    }

    public function addTotal(InterfaceTotal $total)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');
        $this->body['Total'][$dictionary['stat_date']] = $dictionary['stat_total'];
        $this->body['Total'][$dictionary['stat_meetings']] = $total->get($dictionary['stat_meetings']);
        $this->body['Total'][$dictionary['stat_participants']] = $total->get($dictionary['stat_participants']);
        $this->body['Total'][$dictionary['stat_duration'] . $format_duration] = $total->get($dictionary['stat_duration'] . $format_duration);
    }

    public function converterBody()
    {
        $this->body = Converter::convertToGeneral($this, $this->settings);
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
}