<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 14.08.2018
 * Time: 13:30
 */

namespace Statistics\StatisticData\Organizations;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceTotal;

class TotalOrganizations implements InterfaceTotal
{
    private $total = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createTotalCompanies(array $data)
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');

        $this->total[$dictionary['stat_meetings']] = 0;
        $this->total[$dictionary['stat_duration'] . $format_duration] = 0;
        $this->total[$dictionary['stat_participants']] = 0;
        foreach ($data as $row) {
            $this->total[$dictionary['stat_meetings']] += 1;
            $this->total[$dictionary['stat_duration'] . $format_duration] += Converter::convertToMinute($row['stat_meeting_duration']);
            $this->total[$dictionary['stat_participants']] += $row['sum_participants'];
        }
    }

    public function get($id)
    {
        return $this->total[$id];
    }
}