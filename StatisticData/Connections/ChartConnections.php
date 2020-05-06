<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 16.10.2018
 * Time: 15:13
 */

namespace Statistics\StatisticData\Connections;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceChart;

class ChartConnections implements InterfaceChart
{
    const VALUE = 'value';
    const NAME = 'name';
    private $chartConnections = [];
    private $settings;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createChart()
    {
        $connections = $this->settings->getSettings('connections');
        $dictionary = $this->settings->getSettings('dictionary');
        foreach ($connections as $id=>$connection) {
            if ($dictionary['stat_mobile_ios'] === $connection) {
                $id = 'Mobile_other';
                $connection = $dictionary['stat_mobile_other'];
            }
            $this->chartConnections[$id][self::NAME] = $connection;
            $this->chartConnections[$id][self::VALUE] = 0;
        }
        unset($this->chartConnections['Windows_all']);
        unset($this->chartConnections['IE']);
        unset($this->chartConnections['Edge']);
        unset($this->chartConnections['Mobile_all']);
        unset($this->chartConnections['IoS']);
        unset($this->chartConnections['Other']);
    }

    public function addChartData(array $data)
    {
        $h323 = 'H323';
        $sip = 'SIP';
        $windows_undefined = 'Windows_undefined';
        $android = 'Android';
        $mobile_other = 'Mobile_other';
        foreach ($data as $row) {
            if ('end_point' === $row['stat_user_type']) {
                if ($row['stat_user_h323'] > '') {
                    $this->chartConnections[$h323][self::VALUE] += 1;
                }
                if (!($row['stat_user_h323'] > '') && $row['stat_user_sip'] > '') {
                    $this->chartConnections[$sip][self::VALUE] += 1;
                }
            }

            if ('person' === $row['stat_user_type']) {
                if ('desktop' === $row['stat_participant_device']) {
                    if ('Windows' === $row['stat_participant_os']) {
                        if (array_key_exists($row['stat_participant_browser'], $this->settings->getSettings('connections'))) {
                            $this->chartConnections[$row['stat_participant_browser']][self::VALUE] += 1;
                        } else {
                            $this->chartConnections[$windows_undefined][self::VALUE] += 1;
                        }
                    } else {
                        $this->chartConnections[$windows_undefined][self::VALUE] += 1;
                    }
                } elseif ('mobile' === $row['stat_participant_device']) {
                    if ('Android' === $row['stat_participant_os']) {
                        $this->chartConnections[$android][self::VALUE] += 1;
                    } else {
                        $this->chartConnections[$mobile_other][self::VALUE] += 1;
                    }
                }
            }
        }
    }

    public function getChartConnections()
    {
        return $this->chartConnections;
    }

    public function getChartConnectionColumn($column)
    {
        return array_column($this->chartConnections, $column);
    }
}