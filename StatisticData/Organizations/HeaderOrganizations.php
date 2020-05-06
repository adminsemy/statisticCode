<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 14.08.2018
 * Time: 12:32
 */

namespace Statistics\StatisticData\Organizations;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

class HeaderOrganizations implements InterfaceHeader
{
    private $header = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createHeader()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');
        $columns_organ['companies'] = $dictionary['stat_companies'];
        $this->header['companies'] = $dictionary['stat_companies'];
        if (1 === $this->settings->getSettings('meeting'))
            $this->header['meeting'] = $dictionary['stat_meetings'];
        if (1 === $this->settings->getSettings('participant'))
            $this->header['participant'] = $dictionary['stat_participants'];
        if (1 === $this->settings->getSettings('duration'))
            $this->header['duration'] = $dictionary['stat_duration'] . $format_duration;
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
        return $this->header[$id];
    }
}