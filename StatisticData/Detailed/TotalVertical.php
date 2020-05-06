<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 18.07.2018
 * Time: 14:01
 */

namespace Statistics\StatisticData\Detailed;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;
use Statistics\StatisticData\InterfaceTotalVertical;

class TotalVertical implements InterfaceTotalVertical
{
    private $total = [];
    private $settings;
    private $header;


    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header)
    {
        $this->settings = $settings;
        $this->header = $header;
    }

    public function createVerticalTotal(array $statistic)
    {
        if (1 === $this->settings->getSettings('meeting'))
            $this->createType('m');
        if (1 === $this->settings->getSettings('duration'))
            $this->createType('d');
        if (1 === $this->settings->getSettings('participant'))
            $this->createType('p');
            foreach ($statistic as $row) {

            if (1 === $this->settings->getSettings('meeting')) {
                $this->addVerticalData(
                    'm',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    $row['num_meetings']);
            }
            if (1 === $this->settings->getSettings('duration')) {
                $this->addVerticalData(
                    'd',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    Converter::convertToMinute($row['sum_duration']));
            }
            if (1 === $this->settings->getSettings('participant')) {
                $this->addVerticalData(
                    'p',
                    $row['stat_user_type'],
                    $row['stat_user_id'],
                    $row['sum_participants']);
            }
        }
    }

    public function createType($type)
    {
        $category = '';
        if ('m' === $type)
            $category = $this->settings->getSettings('dictionary')['stat_meetings'];
        if ('d' === $type)
            $category = $this->settings->getSettings('dictionary')['stat_duration'];
        if ('p' === $type)
            $category = $this->settings->getSettings('dictionary')['stat_participants'];

        if (!array_key_exists($type, $this->total)) {
            $this->total[$type]['dataRange'] = $this->settings->getSettings('dictionary')['stat_total'];
            $this->total[$type]['category'] = $category;
            foreach ($this->header->getHeader() as $key => $column) {
                $this->total[$type][$key] = 0;
            }
            $this->total[$type]['total'] = 0;
        }
    }

    public function sumDataMember($type, $id, $data = 1)
    {
        if (array_key_exists($id,$this->total[$type]))
            $this->total[$type][$id] += $data;
    }

    public function get($id)
    {
        if (array_key_exists($id,$this->total))
            return $this->total[$id];
        return false;
    }

    private function addVerticalData($type, $user_type, $user_id, $data)
    {
        
        if ('person' === $user_type) {
            $this->sumDataMember($type, 'Total to users', $data);
        }
        if ('end_point' === $user_type) {
            $this->sumDataMember($type, 'Total to terminals', $data);
        }
        if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $user_type) ||
            (1 == $this->settings->getSettings('show_users') && 'person' === $user_type)) {
            $this->sumDataMember($type, $user_id, $data);
        }
        if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $user_type) && $user_id === $this->settings->getSettings('user_id')) {
            $this->sumDataMember($type, $user_id, $data);
        }
    }
}