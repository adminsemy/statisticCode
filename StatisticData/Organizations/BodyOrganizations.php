<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 14.08.2018
 * Time: 13:11
 */

namespace Statistics\StatisticData\Organizations;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceTotal;

class BodyOrganizations implements InterfaceBody
{
    private $body = [];
    private $settings;
    private $total;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceTotal $total)
    {
        $this->settings = $settings;
        $this->total = $total;
    }

    public function createBodyCompanies(array $data)
    {
        $company_id = 'stat_host_company_id';
        $company_name = 'stat_host_company_name';
        $dictionary = $this->settings->getSettings('dictionary');
        $format_duration = $this->settings->getSettings('format_duration');

        foreach ($data as $row) {
            if (!isset($this->body[$row[$company_id]][$dictionary['stat_companies']]))
                $this->body[$row[$company_id]][$dictionary['stat_companies']] = $row[$company_name];

            if (isset($this->body[$row[$company_id]][$dictionary['stat_meetings']]))
                $this->body[$row[$company_id]][$dictionary['stat_meetings']] += 1;
            else {
                $this->body[$row[$company_id]][$dictionary['stat_meetings']] = 1;
            }

            if (isset($this->body[$row[$company_id]][$dictionary['stat_duration'] . $format_duration]))
                $this->body[$row[$company_id]][$dictionary['stat_duration'] . $format_duration] += Converter::convertToMinute($row['stat_meeting_duration']);
            else
                $this->body[$row[$company_id]][$dictionary['stat_duration'] . $format_duration] = Converter::convertToMinute($row['stat_meeting_duration']);

            if (isset($this->body[$row[$company_id]][$dictionary['stat_participants']]))
                $this->body[$row[$company_id]][$dictionary['stat_participants']] += $row['sum_participants'];
            else
                $this->body[$row[$company_id]][$dictionary['stat_participants']] = $row['sum_participants'];
        }
        $this->body['Total'][$dictionary['stat_companies']] = $dictionary['stat_total'];
        $this->body['Total'][$dictionary['stat_meetings']] = $this->total->get($dictionary['stat_meetings']);
        $this->body['Total'][$dictionary['stat_participants']] = $this->total->get($dictionary['stat_participants']);
        $this->body['Total'][$dictionary['stat_duration'] . $format_duration] = $this->total->get($dictionary['stat_duration'] . $format_duration);
    }

    public function convertBodyCompanies()
    {
        $this->body = Converter::convertToOrganizations($this, $this->settings);
    }

    public function getBody()
    {
        return $this->body;
    }
}