<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 07.08.2018
 * Time: 16:23
 */

namespace Statistics\StatisticData\General;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceTotal;

class TotalGeneral implements InterfaceTotal
{
    private $total = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createTotal(array $data, $meeting_video = null)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $this->total[$dictionary['stat_meetings']] = 0;
        $this->total[$dictionary['stat_participants']] = 0;
        $this->total[$dictionary['stat_duration'] . $this->settings->getSettings('format_duration')] = 0;
        foreach ($data as $row) {
            $row_meeting_video = (int)$row['meeting_video'];
            if (is_null($meeting_video) ? true : $row_meeting_video === $meeting_video) {
                $this->total[$dictionary['stat_meetings']] += 1;
                $this->total[$dictionary['stat_participants']] += $row['sum_participants'];
                $this->total[$dictionary['stat_duration'] . $this->settings->getSettings('format_duration')] += Converter::convertToMinute($row['stat_meeting_duration']);
            }
        }
    }

    public function get($id)
    {
        return $this->total[$id];
    }
}