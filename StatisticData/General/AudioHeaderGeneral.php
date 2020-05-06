<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 06.08.2018
 * Time: 21:43
 */

namespace Statistics\StatisticData\General;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

class AudioHeaderGeneral implements InterfaceHeader
{
    private $headers = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createHeaders()
    {
        $dictionary = $this->settings->getSettings('dictionary');
        $this->headers[$dictionary['stat_date']] = $dictionary['stat_date'];
        if (1 === $this->settings->getSettings('meeting'))
            $this->headers[$dictionary['stat_meetings']] = $dictionary['stat_meetings'];
        if (1 === $this->settings->getSettings('participant'))
            $this->headers[$dictionary['stat_participants']] = $dictionary['stat_participants'];
        if (1 === $this->settings->getSettings('duration'))
            $this->headers[$dictionary['stat_duration'] . $this->settings->getSettings('format_duration')] = $dictionary['stat_duration'] . $this->settings->getSettings('format_duration');
    }

    public function getHeader()
    {
        return $this->headers;
    }

    public function getHeaderValues()
    {
        return array_values($this->headers);
    }

    public function getIdHeader($id)
    {
        return $this->headers[$id];
    }
}