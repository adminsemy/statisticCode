<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 08.08.2018
 * Time: 9:30
 */

namespace Statistics\StatisticData\Detailed;

use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceChart;

class ChartHorizontal implements InterfaceChart
{
    private $chartLabels = [];
    private $chartMeetings = [];
    private $chartParticipants = [];
    private $chartDuration = [];
    private $settings;
    private $data = [];
    private $user = [];

    public function __construct(InterfaceNormalizationSettings $settings, array $data_statistic, array $data_user)
    {
        $this->settings = $settings;
        $this->data = $data_statistic;
        $this->user = $data_user;
    }

    public function createChart()
    {
        $i = 0;
        $names = [];
        $unidentified = false;
        $statistic = $this->data;
        foreach ($statistic as $rows) {
            $type = isset($rows['stat_user_type']) ? $rows['stat_user_type'] : '';
            if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $type) ||
                (1 === $this->settings->getSettings('show_users') && 'person' === $type)) {
                if (0 != $rows['stat_user_id']) {
                    $name = $rows['stat_user_fname'] . ' ' . $rows['stat_user_lname'];
                    if (!($rows['stat_user_fname'] > '') && !isset($names[$rows['stat_user_id']]['id'])) {
                        $names[$rows['stat_user_id']]['Name'] = 'Noname ' . $i++;
                        $names[$rows['stat_user_id']]['id'] = $rows['stat_user_id'];
                        $name = $names[$rows['stat_user_id']]['Name'];
                    }
                    if (!($rows['stat_user_fname'] > '') && isset($names[$rows['stat_user_id']]['id'])) {
                        $name = $names[$rows['stat_user_id']]['Name'];
                    }
                    $this->chartLabels[$rows['stat_user_id']] = $name;
                } else {
                    $unidentified = true;
                }
            }
            if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $rows['stat_user_type']) && $rows['stat_user_id'] === USER_ID) {
                $this->chartLabels[$rows['stat_user_id']] = $rows['stat_user_fname'] . ' ' . $rows['stat_user_lname'];
            }
        }
        $this->sortChartLabels();
        if ($unidentified) {
            $this->chartLabels[0] = $this->settings->getSettings('dictionary')['stat_unidentified_users'];
        }
        if (empty($this->chartLabels) && 1 === $this->settings->getSettings('show_detailed'))
            $this->chartLabels[0] = $this->user[0]['user_fname'] . ' ' . $this->user[0]['user_lname'];
        $this->chartMeetings = $this->chartDuration = $this->chartParticipants = array_fill_keys(array_keys($this->chartLabels),0);
    }

    public function addChartData()
    {
        $user_id = $this->settings->getSettings('user_id');
        $data = $this->data;
        foreach ($data as $row) {
            $row['stat_user_type'] = empty($row['stat_user_type']) ? 'empty' : $row['stat_user_type'];
            if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $row['stat_user_type']) ||
                (1 === $this->settings->getSettings('show_users') && 'person' === $row['stat_user_type'])) {
                $this->setChartMeetings($row['stat_user_id'], $row['num_meetings']);
                $this->setChartDuration($row['stat_user_id'], Converter::convertToSecondChart($row['sum_duration']));
                $this->setChartParticipants($row['stat_user_id'], $row['sum_participants']);
            }
            if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $row['stat_user_type']) && $user_id === $row['stat_user_id']) {
                $this->setChartMeetings($row['stat_user_id'], $row['num_meetings']);
                $this->setChartDuration($row['stat_user_id'], Converter::convertToSecondChart($row['sum_duration']));
                $this->setChartParticipants($row['stat_user_id'], $row['sum_participants']);
            }
        }
    }

    public function getChartLabels()
    {
        return array_values($this->chartLabels);
    }

    public function getChartMeetings()
    {
        return array_values($this->chartMeetings);
    }

    public function getChartParticipants()
    {
        return array_values($this->chartParticipants);
    }

    public function getChartDuration()
    {
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

    private function setChartMeetings($id, $data)
    {
        if (array_key_exists($id, $this->chartMeetings))
            $this->chartMeetings[$id] += $data;
    }

    private function setChartParticipants($id, $data)
    {
        if (array_key_exists($id, $this->chartParticipants))
            $this->chartParticipants[$id] += $data;
    }

    private function setChartDuration($id, $data)
    {
        if (array_key_exists($id, $this->chartDuration))
            $this->chartDuration[$id] += $data;
    }

    private function sortChartLabels()
    {
        asort($this->chartLabels);
    }
}