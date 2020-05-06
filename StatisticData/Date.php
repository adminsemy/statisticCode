<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 22.07.2018
 * Time: 15:10
 */

namespace Statistics\StatisticData;


use Statistics\InterfaceNormalizationSettings;
use Statistics\Converter;

class Date implements InterfaceDate
{
    private $date = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function generateDate(array $data)
    {
        foreach ($data as $row) {
            $this->set($row['meeting_stat_day'], $row['stat_date']);
        }
    }

    public function set($format_date, $date)
    {
        if ('week_day' === $this->settings->getSettings('display'))
            $format_date = $this->settings->getSettings('name_day')[$format_date];
        $this->date[$format_date] = Converter::weekString($format_date, $date, $this->settings);
        if ('total_only' === $this->settings->getSettings('display'))
            $this->date[] = $this->settings->getSettings('dictionary')['stat_total'];
    }
    public function get()
    {
        return array_values($this->date);
    }
}