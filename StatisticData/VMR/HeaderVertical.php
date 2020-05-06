<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 15.07.2018
 * Time: 17:13
 */

namespace Statistics\StatisticData\VMR;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

class HeaderVertical implements InterfaceHeader
{
    private $header = [];
    private $header_sort = [];
    private $settings;
    private $rooms = [];

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
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


    public function setHeaderData(array $header)
    {
        $data = 1;
        $auto_vmr = false;
        $dictionary = $this->settings->getSettings('dictionary');
        $auto_room = $this->settings->getSettings('dictionary')['stat_auto_rooms'];
        foreach ($header as $row) {
            if (0 === $this->settings->getSettings('meeting'))
                $data = $row['sum_participants'];
            if (0 === $this->settings->getSettings('participant'))
                $data = $row['stat_meeting_duration'];
            $room = $this->createNameRoom($row['stat_participant_room']);
            if ($room === $auto_room) {
                $auto_vmr = true;
                $this->addHeaderSort($dictionary['stat_auto_rooms'], $room, $data);
            }
            else {
                $this->header[($row['stat_participant_room'])] = $room;
                $this->addHeaderSort($row['stat_participant_room'], $room, $data);
            }
        }
        asort($this->header);
        if ($auto_vmr)
            $this->header[$dictionary['stat_auto_rooms']] = $dictionary['stat_auto_rooms'];
        if ('total_only' === $this->settings->getSettings('display'))
            $this->sortHeader();
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getHeaderSort()
    {
        return $this->header_sort;
    }

    public function getHeaderValues()
    {
        return array_values($this->header);
    }

    public function getIdHeader($id)
    {
        if (!array_key_exists($id, $this->header))
            return false;
        return $this->header[$id];
    }

    public function getRooms()
    {
        return $this->rooms;
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

    private function addHeaderSort ($key, $name, $data)
    {
        if (!array_key_exists($key, $this->header_sort)) {
            $this->header_sort[$key]['data'] = 0;
            $this->header_sort[$key]['name'] = $name;
            $this->header_sort[$key]['key'] = $key;
        }
        $this->header_sort[$key]['data'] += $data;
    }

    private function sortHeader()
    {
        $array_sort = [];
        $sort_name = array_column($this->header_sort, 'name');
        $sort_data = array_column($this->header_sort, 'data');
        array_multisort($sort_data, SORT_DESC, $sort_name, SORT_ASC, $this->header_sort);
        foreach ($this->header_sort as $item) {
            $array_sort[$item['key']] = $this->header[$item['key']];
        }
        $this->header = $array_sort;
    }
}