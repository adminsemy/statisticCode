<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 15.07.2018
 * Time: 17:14
 */

namespace Statistics\StatisticData\Detailed;


use Statistics\InterfaceNormalizationSettings;
use Statistics\Converter;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;
use Statistics\StatisticData\InterfaceTotalVertical;

class BodyVertical implements InterfaceBody
{
    private $body = [];
    private $settings;
    private $header;
    private $total;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header, InterfaceTotalVertical $total)
    {
        $this->settings = $settings;
        $this->header = $header;
        $this->total = $total;
    }

    public function createVerticalBody(array $statistic)
    {
        foreach ($statistic as $row) {

            if (1 === $this->settings->getSettings('meeting')) {
                $this->addVerticalData(
                    $row['meeting_stat_day'],
                    $row['stat_date'],
                    'm',
                    'stat_meetings',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    $row['num_meetings']);
            }
            if (1 === $this->settings->getSettings('duration')) {
                $this->addVerticalData(
                    $row['meeting_stat_day'],
                    $row['stat_date'],
                    'd',
                    'stat_duration',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    Converter::convertToMinute($row['sum_duration']));
            }
            if (1 === $this->settings->getSettings('participant')) {
                $this->addVerticalData(
                    $row['meeting_stat_day'],
                    $row['stat_date'],
                    'p',
                    'stat_participants',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    $row['sum_participants']);
            }
        }
    }

    public function createVerticalBodyTotal(array $total)
    {
        foreach ($total as $row) {

            if (!empty($this->body[$row['meeting_stat_day'] . " - m"])) {
                $this->addVerticalTotal($row['meeting_stat_day'], 'm', 1);
                $this->total->sumDataMember('m', 'total',  1);
            }

            if (!empty($this->body[$row['meeting_stat_day'] . " - d"])) {
                $this->addVerticalTotal($row['meeting_stat_day'], 'd',  Converter::convertToMinute($row['stat_meeting_duration']));
                $this->total->sumDataMember('d', 'total',  Converter::convertToMinute($row['stat_meeting_duration']));
            }

            if (!empty($this->body[$row['meeting_stat_day'] . " - p"])) {
                $this->addVerticalTotal($row['meeting_stat_day'], 'p', $row['sum_participants']);
                $this->total->sumDataMember('p', 'total',  $row['sum_participants']);
            }
        }
        $this->body['Total - m'] = $this->total->get('m') ? $this->total->get('m') : '0';
        $this->body['Total - d'] = $this->total->get('d') ? $this->total->get('d') : '0';
        $this->body['Total - p'] = $this->total->get('p') ? $this->total->get('p') : '0';
    }

    public function convertVerticalBody()
    {
        $this->body = Converter::convertToVerticalData($this, $this->header, $this->settings);
    }

    public function getBody()
    {
        return $this->body;
    }

    private function addVerticalTotal($format_date, $type, $data = 1)
    {
        $this->body[$format_date . " - $type"]['total'] += $data;
    }

    private function addVerticalData($format_date, $date, $type, $name_category, $user_type, $user_id, $data)
    {
        if (!isset($this->body[$format_date . " - $type"])) {
            $num_day_week = $format_date;
            if ('week_day' === $this->settings->getSettings('display') )
                $num_day_week = $this->settings->getSettings('name_day')[$format_date];
            $this->body[$format_date . " - $type"]['dataRange'] = Converter::weekString($num_day_week, $date, $this->settings);

            $this->body[$format_date . " - $type"]['category'] = $this->settings->getSettings('dictionary')[$name_category];
            foreach ($this->header->getHeader() as $key => $column) {
                $this->body[$format_date . " - $type"][$key] = 0;
            }
            $this->body[$format_date . " - $type"]['total'] = 0;
        }
        if ('person' === $user_type) {
            $this->body[$format_date . " - $type"]['Total to users'] += $data;
        }
        if ('end_point' === $user_type) {
            $this->body[$format_date . " - $type"]['Total to terminals'] += $data;
        }
        if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $user_type) ||
            (1 == $this->settings->getSettings('show_users') && 'person' === $user_type)) {
            if (!array_key_exists($user_id, $this->body[$format_date . " - $type"]))
                $this->body[$format_date . " - $type"][$user_id] = 0;
            $this->body[$format_date . " - $type"][$user_id] += $data;
        }
        if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $user_type) && $user_id === $this->settings->getSettings('user_id')) {
            if (!isset($this->body[$format_date . " - $type"][$user_id]))
                $this->body[$format_date . " - $type"][$user_id] = 0;
            $this->body[$format_date . " - $type"][$user_id] += $data;
        }
    }
}