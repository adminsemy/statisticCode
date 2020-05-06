<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 25.10.2018
 * Time: 13:28
 */

namespace Statistics\StatisticData\VMR;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;

class VmrVertical implements InterfaceBody
{
    private $body = [];
    private $body_total = [];
    private $body_number_vmr = [];
    private $rooms = [];
    private $settings;
    private $header;
    private $number_vmr;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header, InterfaceNumberVMR $numberVMR)
    {
        $this->settings = $settings;
        $this->header = $header;
        $this->number_vmr = $numberVMR;
    }

    public function createRooms(array $rooms)
    {
        foreach ($rooms as $row) {
            $name = empty($row['meeting_standing_desc']) ? $row['meeting_standing_name'] : $row['meeting_standing_desc'];
            $this->rooms[$row['meeting_standing_id']] = $name;
            if (!empty($row['meeting_standing_alias1']))
                $this->rooms[$row['meeting_standing_alias1']] = $name;
            if (!empty($row['meeting_standing_alias2']))
                $this->rooms[$row['meeting_standing_alias2']] = $name;
            if (!empty($row['meeting_standing_alias3']))
                $this->rooms[$row['meeting_standing_alias3']] = $name;
            if (!empty($row['meeting_standing_alias4']))
                $this->rooms[$row['meeting_standing_alias4']] = $name;
        }
    }

    public function createVmr(array $data)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $m = $this->settings->getSettings('m');
        $d = $this->settings->getSettings('d');
        $p = $this->settings->getSettings('p');
        $this->createNumberVmr();
        foreach ($data as $row) {
            $room = $this->createNameRoom($row['stat_participant_room']);
            if (1 === $this->settings->getSettings('meeting')) {
                $this->addVerticalData($row['meeting_stat_day'], $row['stat_date'], $m, $dictionary['stat_meetings'], $room, 1);
            }
            if (1 === $this->settings->getSettings('duration')) {
                $this->addVerticalData($row['meeting_stat_day'], $row['stat_date'], $d, $dictionary['stat_duration'], $room, Converter::convertToMinute($row['stat_meeting_duration']));
            }
            if (1 === $this->settings->getSettings('participant')) {
                $this->addVerticalData($row['meeting_stat_day'], $row['stat_date'], $p, $dictionary['stat_participants'], $room, $row['sum_participants']);
            }
        }
        if (!empty($this->body))
            array_unshift($this->body, $this->body_number_vmr[$this->settings->getSettings('alias_number')]);
        if (empty($this->body)) {
            if (1 === $this->settings->getSettings('meeting'))
                $this->createVerticalDataTotal($m, $dictionary['stat_meetings']);
            if (1 === $this->settings->getSettings('duration'))
                $this->createVerticalDataTotal($d, $dictionary['stat_duration']);
            if (1 === $this->settings->getSettings('participant'))
                $this->createVerticalDataTotal($p, $dictionary['stat_duration']);
        }
    }

    public function convert()
    {
        $this->body += $this->body_total;
        $this->body = Converter::convertToVerticalData($this, $this->header, $this->settings);
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getBodyTotal()
    {
        return $this->body_total;
    }

    private function addVerticalData($format_date, $date, $type, $name_category, $room, $data)
    {
        $data_range = $this->settings->getSettings('dataRange');
        $category = $this->settings->getSettings('category');
        $total_lower = strtolower($this->settings->getSettings('total'));
        $total = $this->settings->getSettings('total');
        if (!isset($this->body[$format_date . " - $type"])) {
            $num_day_week = $format_date;
            if ('week_day' === $this->settings->getSettings('display') )
                $num_day_week = $this->settings->getSettings('name_day')[$format_date];
            $this->body[$format_date . " - $type"][$data_range] = Converter::weekString($num_day_week, $date, $this->settings);

            $this->body[$format_date . " - $type"][$category] = $name_category;
            foreach ($this->header->getHeader() as $key => $column) {
                $this->body[$format_date . " - $type"][$key] = 0;
            }
            $this->body[$format_date . " - $type"][$total_lower] = 0;
        }
        $this->createVerticalDataTotal($type, $name_category);
        $this->body[$format_date . " - $type"][$room] += $data;
        $this->body[$format_date . " - $type"][$total_lower] += $data;
        if (!isset($this->body_total[$total . " - $type"][$room]))
            $this->body_total[$total . " - $type"][$room] = 0;
        $this->body_total[$total . " - $type"][$room] += $data;
        if (!isset($this->body_total[$total . " - $type"][$total_lower]))
            $this->body_total[$total . " - $type"][$total_lower] = 0;
        $this->body_total[$total . " - $type"][$total_lower] += $data;
    }

    private function createVerticalDataTotal($type, $name_category)
    {
        $data_range = $this->settings->getSettings('dataRange');
        $category = $this->settings->getSettings('category');
        $total_lower = strtolower($this->settings->getSettings('total'));
        $total = $this->settings->getSettings('total');
        if (!isset($this->body_total[$total . " - $type"])) {
            $this->body_total[$total . " - $type"][$data_range] = $this->settings->getSettings('dictionary')['stat_total'];
            $this->body_total[$total . " - $type"][$category] = $name_category;
            foreach ($this->header->getHeader() as $key => $column) {
                $this->body_total[$total . " - $type"][$key] = 0;
            }
            $this->body_total[$total . " - $type"][$total_lower] = 0;
        }
    }

    private function createNameRoom($room_table)
    {
        $auto_room = $this->settings->getSettings('dictionary')['stat_auto_rooms'];
        $start_room = (int)$this->settings->getSettings('meeting_id_start');
        $end_room = (int)$this->settings->getSettings('meeting_id_finish');
        $step_room = (int)$this->settings->getSettings('meeting_id_delta');
        $number_room = abs((int)$room_table);
        $is_auto_room = ($number_room - $start_room)%$step_room;
        if ($number_room <= $end_room && $number_room >= $start_room && 0 === $is_auto_room)
            $room = $auto_room;
        else
            $room = $room_table;
        if (2 === $this->settings->getSettings('planned') && $room === $auto_room)
            $room = $room_table;
        return $room;
    }

    private function createNumberVmr()
    {
        $data_range = $this->settings->getSettings('dataRange');
        $category = $this->settings->getSettings('category');
        $type = $this->settings->getSettings('alias_number');
        if (!isset($this->body_number_vmr[$type])) {
            $this->body_number_vmr[$type][$data_range] = $this->settings->getSettings('dictionary')['stat_vmr_number'];
            $this->body_number_vmr[$type][$category] = $type;
            foreach ($this->header->getHeader() as $key => $column) {
                $this->body_number_vmr[$type][$key] = $this->number_vmr->getNumberVmrName($column);
            }
        }
    }
}