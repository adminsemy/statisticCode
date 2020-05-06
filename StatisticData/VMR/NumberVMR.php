<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 02.12.2018
 * Time: 17:48
 */

namespace Statistics\StatisticData\VMR;


use Statistics\InterfaceNormalizationSettings;

class NumberVMR implements InterfaceNumberVMR
{
    private $number_vmr = [];
    private $name_vmr;
    private $rooms;
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings, array $name_vmr, array $rooms)
    {
        $this->settings = $settings;
        $this->name_vmr = $name_vmr;
        $this->createRooms($rooms);
        $this->createNumberVmr();
    }

    public function getNumberVmr()
    {
        return $this->number_vmr;
    }

    public function getRooms()
    {
        return $this->rooms;
    }

    public function getNumberVmrVertical()
    {
        $result = [];
        $result[$this->settings->getSettings('alias_name')] = array_keys($this->number_vmr);
        $result[$this->settings->getSettings('alias_number')] = array_values($this->number_vmr);
        return $result;
    }

    public function getNumberVmrName($id)
    {
        return $this->number_vmr[$id];
    }

    private function createRooms(array $rooms)
    {
        foreach ($rooms as $row) {
            $name = empty($row['meeting_standing_alias1']) ? $row['meeting_standing_id'] : $row['meeting_standing_alias1'];
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

    private function createNumberVmr()
    {
        $auto_vmr = $this->settings->getSettings('dictionary')['stat_auto_rooms'];
        $result = [];
        foreach ($this->name_vmr as $key=>$name) {
            $value = '';
            if (array_key_exists($key, $this->rooms))
                $value = $this->rooms[$key];
            $result[$name] = $value;
        }
        if (array_key_exists($auto_vmr, $result))
            $result[$auto_vmr] = $auto_vmr;
        $this->number_vmr = $result;
    }
}