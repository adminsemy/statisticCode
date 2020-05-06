<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 03.08.2018
 * Time: 23:27
 */

namespace Statistics\StatisticData\Detailed;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

class HeaderHorizontal implements InterfaceHeader
{
    const ASC = 'ASC';
    const DESC = 'DESC';
    private $header = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function setHorizontalData(array $statistic)
    {
        foreach ($statistic as $rows) {
            if ('total_only' === $this->settings->getSettings('display'))
                break;
            $name_day_week = $rows['meeting_stat_day'];
            if ('week_day' === $this->settings->getSettings('display'))
                $name_day_week = $num_day_week = $this->settings->getSettings('name_day')[$rows['meeting_stat_day']];
            $this->header[$rows['meeting_stat_day']] = Converter::weekString($name_day_week, $rows['stat_date'], $this->settings);
        }
        $this->header['Total'] = $this->settings->getSettings('dictionary')['stat_total'];
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getHeaderValues()
    {
        return array_values($this->header);
    }

    public function getIdHeader($id)
    {
        if (array_key_exists($id, $this->header))
            return $this->header[$id];
        return false;
    }
}