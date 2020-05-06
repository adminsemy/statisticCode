<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 26.10.2018
 * Time: 8:03
 */

namespace Statistics\StatisticData\VMR;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;

class VmrHorizontal implements InterfaceBody
{
    private $vmr = [];
    private $vmr_auto = [];
    private $vmr_total = [];
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
        $device = $this->settings->getSettings('device');
        $m = $this->settings->getSettings('m') . ' ';
        $d = $this->settings->getSettings('d') . ' ';
        $p = $this->settings->getSettings('p') . ' ';
        $total = $this->settings->getSettings('total');
        $stat_total = $dictionary['stat_total'];
        foreach ($data as $row) {
            $room = $this->createNameRoom($row['stat_participant_room']);
            $this->addDataVmr($room, $row['meeting_stat_day'], Converter::convertToMinute($row['stat_meeting_duration']), $row['sum_participants']);
            $this->addDataTotal($row['meeting_stat_day'], Converter::convertToMinute($row['stat_meeting_duration']), $row['sum_participants']);
        }
        if (empty($this->vmr)) {
            $this->vmr_total[$total][$device] = $dictionary['stat_total'];
            $this->vmr_total[$total][$m . $stat_total] = 0;
            $this->vmr_total[$total][$d . $stat_total] = 0;
            $this->vmr_total[$total][$p . $stat_total] = 0;
        }
    }

    public function getVmrAuto()
    {
        return $this->vmr_auto;
    }

    public function getRooms()
    {
        return $this->rooms;
    }

    public function getVmrTotal()
    {
        return $this->vmr_total;
    }

    public function convert()
    {
        $m = $this->settings->getSettings('m') . ' ';
        $d = $this->settings->getSettings('d') . ' ';
        $p = $this->settings->getSettings('p') . ' ';
        $stat_total = $this->settings->getSettings('dictionary')['stat_total'];

        $name = array_column($this->vmr, $this->settings->getSettings('device'));
        $name = array_map('strtolower', $name);
        $this->sort($name, SORT_ASC, $this->vmr);
        $this->vmr += $this->vmr_auto;
        if ('total_only' === $this->settings->getSettings('display')) {
            $columns = $m;
            if (0 === $this->settings->getSettings('meeting'))
                $columns = $p;
            if (0 === $this->settings->getSettings('participant'))
                $columns = $d;
            $name = array_column($this->vmr, $columns . $stat_total);
            $name = array_map('strtolower', $name);
            $this->sort($name, SORT_DESC, $this->vmr);
        }
        $this->vmr += $this->vmr_total;
        $this->vmr = Converter::convertToHorizontalData($this, $this->settings);
    }

    public function getBody()
    {
        return $this->vmr;
    }

    private function addDataVmr($room, $date, $duration, $participant)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $m = $this->settings->getSettings('m') . ' ';
        $d = $this->settings->getSettings('d') . ' ';
        $p = $this->settings->getSettings('p') . ' ';
        $device = $this->settings->getSettings('device');
        $alias_number = $this->settings->getSettings('alias_number');
        $total_only = $this->settings->getSettings('total_only');
        $stat_total = $dictionary['stat_total'];
        if ($room === $this->settings->getSettings('dictionary')['stat_auto_rooms'])
            $vmr =& $this->vmr_auto;
        else
            $vmr =& $this->vmr;
        if (!isset($vmr[$room])) {
            $vmr[$room][$alias_number] = $this->number_vmr->getNumberVmrName($room);
            $vmr[$room][$device] = $room;
            foreach ($this->header->getHeader() as $key => $column) {
                if ($total_only === $this->settings->getSettings('display'))
                    break;
                $vmr[$room][$m . $column] = 0;
                $vmr[$room][$d . $column] = 0;
                $vmr[$room][$p . $column] = 0;
            }
            $vmr[$room][$m . $stat_total] = 0;
            $vmr[$room][$d . $stat_total] = 0;
            $vmr[$room][$p . $stat_total] = 0;
        }
        if (!isset($vmr[$room][$m . $date]))
            $vmr[$room][$m . $date] = 0;
        $vmr[$room][$m . $date] += 1;
        if (!isset($vmr[$room][$d . $date]))
            $vmr[$room][$d . $date] = 0;
        $vmr[$room][$d . $date] += $duration;
        if (!isset($vmr[$room][$p . $date]))
            $vmr[$room][$p . $date] = 0;
        $vmr[$room][$p . $date] += $participant;
        $vmr[$room][$m . $stat_total] += 1;
        $vmr[$room][$d . $stat_total] += $duration;
        $vmr[$room][$p . $stat_total] += $participant;
    }
    
    private function addDataTotal($date, $duration, $participant)
    {
        $total = $this->settings->getSettings('total');
        $device = $this->settings->getSettings('device');
        $total_only = $this->settings->getSettings('total_only');
        $m = $this->settings->getSettings('m') . ' ';
        $d = $this->settings->getSettings('d') . ' ';
        $p = $this->settings->getSettings('p') . ' ';
        $stat_total = $this->settings->getSettings('dictionary')['stat_total'];
        if (!isset($this->vmr_total[$total])) {
            $this->vmr_total[$total][$device] = $stat_total;
            foreach ($this->header->getHeader() as $key => $column) {
                if ($total_only === $this->settings->getSettings('display'))
                    break;
                $this->vmr_total[$total][$m . $column] = 0;
                $this->vmr_total[$total][$d . $column] = 0;
                $this->vmr_total[$total][$p . $column] = 0;
            }
            $this->vmr_total[$total][$m . $stat_total] = 0;
            $this->vmr_total[$total][$d . $stat_total] = 0;
            $this->vmr_total[$total][$p . $stat_total] = 0;
        }
        if (!isset($this->vmr_total[$total][$m . $date]))
            $this->vmr_total[$total][$m . $date] = 0;
        $this->vmr_total[$total][$m . $date] += 1;
        if (!isset($this->vmr_total[$total][$d . $date]))
            $this->vmr_total[$total][$d . $date] = 0;
        $this->vmr_total[$total][$d . $date] += $duration;
        if (!isset($this->vmr_total[$total][$p . $date]))
            $this->vmr_total[$total][$p . $date] = 0;
        $this->vmr_total[$total][$p . $date] += $participant;
        $this->vmr_total[$total][$m . $stat_total] += 1;
        $this->vmr_total[$total][$d . $stat_total] += $duration;
        $this->vmr_total[$total][$p . $stat_total] += $participant;
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
        if (array_key_exists($room_table, $this->rooms))
            $room = $this->rooms[$room_table];
        if (2 === $this->settings->getSettings('planned') && $room === $auto_room)
            $room = $room_table;
        return $room;
    }

    private function sort($name, $arg, &$array)
    {
        array_multisort($name, $arg, $array);
    }
}