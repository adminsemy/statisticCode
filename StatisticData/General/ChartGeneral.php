<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 08.08.2018
 * Time: 13:15
 */

namespace Statistics\StatisticData\General;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceChart;

class ChartGeneral implements InterfaceChart
{
    private $chartLabels = [];
    private $chartMeetings = [];
    private $chartParticipants = [];
    private $chartDuration = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array $data
     */
    public function createBody(array $data)
    {
        foreach ($data as $row) {
            $format_date = $row['meeting_stat_day'];
            if ('week_day' === $this->settings->getSettings('display'))
                $format_date = $this->settings->getSettings('name_day')[$format_date];
            $this->chartLabels[$format_date] = Converter::weekString($format_date, $row['stat_date'], $this->settings);

            if (isset($this->chartMeetings[$row['meeting_stat_day']])) {
                $this->chartMeetings[$row['meeting_stat_day']] += 1;
            }
            else {
                $this->chartMeetings[$row['meeting_stat_day']] = 1;
            }
            if (isset($this->chartParticipants[$row['meeting_stat_day']])) {
                $this->chartParticipants[$row['meeting_stat_day']] += $row['sum_participants'];
            }
            else {
                $this->chartParticipants[$row['meeting_stat_day']] = $row['sum_participants'];
            }
            if (isset($this->chartDuration[$row['meeting_stat_day']])) {
                $this->chartDuration[$row['meeting_stat_day']] += Converter::convertToSecondChart($row['stat_meeting_duration']);
            }
            else {
                $this->chartDuration[$row['meeting_stat_day']] = Converter::convertToSecondChart($row['stat_meeting_duration']);
            }
        }
    }

    public function getChartLabels()
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $this->chartLabels = array();
            $this->chartLabels[] = $this->settings->getSettings('dictionary')['stat_total'];
        }
        return array_values($this->chartLabels);
    }

    public function getChartMeetings()
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $sum_meetings = array_sum($this->chartMeetings);
            $this->chartMeetings = array();
            $this->chartMeetings[] = $sum_meetings;
        }
        return array_values($this->chartMeetings);
    }

    public function getChartParticipants()
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $sum_participants = array_sum($this->chartParticipants);
            $this->chartParticipants = array();
            $this->chartParticipants[] = $sum_participants;
        }
        return array_values($this->chartParticipants);
    }

    public function getChartDuration()
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $sum_durations = array_sum($this->chartDuration);
            $this->chartDuration = array();
            $this->chartDuration[] = $sum_durations;
        }
        return array_values($this->chartDuration);
    }

    public function getChart()
    {
        $charts_all = [];
        if (1 === $this->settings->getSettings('meeting'))
            $charts_all[$this->settings->getSettings('chartMeetingsData')] = $this->getChartMeetings();
        if (1 === $this->settings->getSettings('participant'))
            $charts_all[$this->settings->getSettings('chartParticipantsData')] = $this->getChartParticipants();
        if (1 === $this->settings->getSettings('duration'))
            $charts_all[$this->settings->getSettings('chartDurationData')] = $this->getChartDuration();
        return $charts_all;
    }
}