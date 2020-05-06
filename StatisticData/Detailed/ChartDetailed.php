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

class ChartDetailed implements InterfaceChart
{
    private $chartLabels = [];
    private $flipChartLabels = [];
    private $chartNameUsers = [];
    private $chartMeetings = [];
    private $chartParticipants = [];
    private $chartDuration = [];
    private $settings;
    private $data = [];
    private $data_total = [];
    private $user = [];
    private $label_name;
    private $data_name;
    private $stat_total_for_users;
    private $stat_total_for_terminals;
    private $stat_total;


    public function __construct(InterfaceNormalizationSettings $settings, array $data_statistic, array $data_total, array $data_user)
    {
        $this->settings = $settings;
        $this->data = $data_statistic;
        $this->data_total = $data_total;
        $this->user = $data_user;
        $this->label_name = $settings->getSettings('label_name');
        $this->data_name = $settings->getSettings('data_name');
        $this->stat_total_for_users = $settings->getSettings('dictionary')['stat_total_for_users'];
        $this->stat_total_for_terminals = $settings->getSettings('dictionary')['stat_total_for_terminals'];
        $this->stat_total = $settings->getSettings('dictionary')['stat_total'];
    }

    public function createChart()
    {
        $i = 0;
        $names = [];
        $unidentified = false;
        $statistic = $this->data;
        $type = '';
        foreach ($statistic as $rows) {
            $this->setChartLabels($rows['meeting_stat_day'], $rows['stat_date']);
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
                    $this->chartNameUsers[$rows['stat_user_id']] = $name;
                } else {
                    $unidentified = true;
                }
            }
            if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $rows['stat_user_type']) && $rows['stat_user_id'] === USER_ID) {
                $this->chartNameUsers[$rows['stat_user_id']] = $this->user[0]['user_fname'] . ' ' . $this->user[0]['user_lname'];
            }
        }
        $this->sortChartNameUsers();
        if ($unidentified) {
            $this->chartNameUsers[0] = $this->settings->getSettings('dictionary')['stat_unidentified_users'];
        }
        /*if ('total_only' === $this->settings->getSettings('display'))
            $this->chartNameUsers = [];*/
        if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $type) ||
            (1 === $this->settings->getSettings('show_users') && 'person' === $type))
            $this->chartNameUsers = [];
        if (empty($this->chartNameUsers) && 1 === $this->settings->getSettings('show_detailed'))
            $this->chartNameUsers[$this->user[0]['user_id']] = $this->user[0]['user_fname'] . ' ' . $this->user[0]['user_lname'];
        $this->flipChartLabels = array_values(array_flip($this->chartLabels));
        $this->createAllCharts();
    }

    public function addChartData()
    {
        $data = $this->data;
        foreach ($data as $row) {
            $row['stat_user_type'] = empty($row['stat_user_type']) ? 'empty' : $row['stat_user_type'];
            $this->setChartMeetings($row['stat_user_id'], $row['num_meetings'], $row['meeting_stat_day'], $row['stat_user_type']);
            $this->setChartDuration($row['stat_user_id'], Converter::convertToSecondChart($row['sum_duration']), $row['meeting_stat_day'], $row['stat_user_type']);
            $this->setChartParticipants($row['stat_user_id'], $row['sum_participants'], $row['meeting_stat_day'], $row['stat_user_type']);
        }
        foreach ($this->data_total as $row) {
            $this->setChartTotal($row['meeting_stat_day'], 1, $row['sum_participants'], Converter::convertToMinute($row['stat_meeting_duration']));
        }
    }

    public function getChartLabels()
    {
        return array_values($this->chartLabels);
    }

    public function getFlipChartLabels()
    {
        return ($this->flipChartLabels);
    }

    public function getChartNameUsers()
    {
        return ($this->chartNameUsers);
    }

    public function getChartMeetings()
    {
        if ('devices' === $this->settings->getSettings('view')) {
            unset($this->chartMeetings[$this->stat_total_for_users]);
            unset($this->chartMeetings[$this->stat_total]);
        }
        return array_values($this->chartMeetings);
    }

    public function getChartParticipants()
    {
        if ('devices' === $this->settings->getSettings('view')) {
            unset($this->chartParticipants[$this->stat_total_for_users]);
            unset($this->chartParticipants[$this->stat_total]);
        }
        return array_values($this->chartParticipants);
    }

    public function getChartDuration()
    {
        if ('devices' === $this->settings->getSettings('view')) {
            unset($this->chartDuration[$this->stat_total_for_users]);
            unset($this->chartDuration[$this->stat_total]);
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

    private function createAllCharts()
    {
        $array_data_empty = [];
        foreach (array_values(array_flip($this->flipChartLabels)) as $key) {
            $array_data_empty[$key] = 0;
        }
        foreach ($this->chartNameUsers as $id=> $chartLabelName) {
            $this->chartMeetings[$id] = [
                $this->label_name => $chartLabelName,
                $this->data_name => $array_data_empty
            ];
        }
        $this->chartMeetings[$this->stat_total_for_users] = [
            $this->label_name => $this->stat_total_for_users,
            $this->data_name => $array_data_empty
        ];
        $this->chartMeetings[$this->stat_total_for_terminals] = [
            $this->label_name => $this->stat_total_for_terminals,
            $this->data_name => $array_data_empty
        ];
        $this->chartMeetings[$this->stat_total] = [
            $this->label_name => $this->stat_total,
            $this->data_name => $array_data_empty
        ];
        $this->chartDuration = $this->chartParticipants = $this->chartMeetings;
    }

    private function setChartLabels($day, $date)
    {
        $format_date = $day;
        if ('week_day' === $this->settings->getSettings('display'))
            $format_date = $this->settings->getSettings('name_day')[$format_date];
        if ('total_only' === $this->settings->getSettings('display'))
            $this->chartLabels[0] = $this->settings->getSettings('dictionary')['stat_total'];
        else
            $this->chartLabels[$day] = Converter::weekString($format_date, $date, $this->settings);
    }

    private function setChartMeetings($id, $data, $date, $type)
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $date = $this->settings->getSettings('dictionary')['stat_total'];
        }
        $date_search = array_search($date, $this->flipChartLabels);
        $data_name = $this->settings->getSettings('data_name');
        if (array_key_exists($id, $this->chartMeetings))
            $this->chartMeetings[$id][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('person'))
            $this->chartMeetings[$this->stat_total_for_users][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('end_point'))
            $this->chartMeetings[$this->stat_total_for_terminals][$data_name][$date_search] += $data;
    }

    private function setChartParticipants($id, $data, $date, $type)
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $date = $this->settings->getSettings('dictionary')['stat_total'];
        }
        $date_search = array_search($date, $this->flipChartLabels);
        $data_name = $this->settings->getSettings('data_name');
        if (array_key_exists($id, $this->chartParticipants))
            $this->chartParticipants[$id][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('person'))
            $this->chartParticipants[$this->stat_total_for_users][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('end_point'))
            $this->chartParticipants[$this->stat_total_for_terminals][$data_name][$date_search] += $data;
    }

    private function setChartDuration($id, $data, $date, $type)
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $date = $this->settings->getSettings('dictionary')['stat_total'];
        }
        $date_search = array_search($date, $this->flipChartLabels);
        $data_name = $this->settings->getSettings('data_name');
        if (array_key_exists($id, $this->chartDuration))
            $this->chartDuration[$id][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('person'))
            $this->chartDuration[$this->stat_total_for_users][$data_name][$date_search] += $data;
        if ($type === $this->settings->getSettings('end_point'))
            $this->chartDuration[$this->stat_total_for_terminals][$data_name][$date_search] += $data;
    }

    private function setChartTotal($date, $num_meetings, $sum_participants, $sum_duration)
    {
        if ('total_only' === $this->settings->getSettings('display')) {
            $date = $this->settings->getSettings('dictionary')['stat_total'];
        }
        $date_search = array_search($date, $this->flipChartLabels);
        $data_name = $this->settings->getSettings('data_name');
        $this->chartMeetings[$this->stat_total][$data_name][$date_search] += $num_meetings;
        $this->chartParticipants[$this->stat_total][$data_name][$date_search] += $sum_participants;
        $this->chartDuration[$this->stat_total][$data_name][$date_search] += Converter::convertToSecondChart($sum_duration);
    }

    private function sortChartNameUsers()
    {
        asort($this->chartNameUsers);
    }
}