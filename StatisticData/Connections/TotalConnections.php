<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 13.08.2018
 * Time: 12:51
 */

namespace Statistics\StatisticData\Connections;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;
use Statistics\StatisticData\InterfaceTotal;

class TotalConnections implements InterfaceTotal
{
    public $total = [];
    private $settings;
    private $header;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header)
    {
        $this->settings = $settings;
        $this->header = $header;
    }

    public function createTotal()
    {
        $connections = $this->settings->getSettings('connections');
        foreach ($connections as $id_connect=>$connection) {
            $this->total[$id_connect]['dataRange'] = $this->settings->getSettings('dictionary')['stat_total'];
            $this->total[$id_connect]['category'] = $connection;
            foreach ($this->header->getHeader() as $key => $column) {
                $this->total[$id_connect][$key] = 0;
            }
            $this->total[$id_connect]['total'] = 0;
        }
        $this->total['111111'] = 1;

    }

    public function setTotalData(array $data)
    {
        $h323 = 'H323';
        $sip = 'SIP';
        $other = 'Other';
        $windows_all = 'Windows_all';
        $windows_undefined = 'Windows_undefined';
        $mobile_all = 'Mobile_all';
        $android = 'Android';
        $ios = 'IoS';

        foreach ($data as $row) {
            if ('end_point' === $row['stat_user_type']) {
                if ($row['stat_user_h323'] > '') {
                    $this->addTotalData($h323, 'Total to terminals');
                    $this->addTotalData($h323, 'total');
                }
                if (!($row['stat_user_h323'] > '') && $row['stat_user_sip'] > '') {
                    $this->addTotalData($sip, 'Total to terminals');
                    $this->addTotalData($sip, 'total');
                }
            }
            if ('end_point' === $row['stat_user_type'] && 1 === $this->settings->getSettings('show_terminals')) {

                if ($row['stat_user_h323'] > '') {
                    $this->addTotalData($h323, $row['stat_user_id']);
                }
                if (!($row['stat_user_h323'] > '') && $row['stat_user_sip'] > '') {
                    $this->addTotalData($sip, $row['stat_user_id']);
                }
                if (!($row['stat_user_h323'] > '') && !($row['stat_user_sip'] > '')) {
                    $this->addTotalData($other, $row['stat_user_id']);
                }
            }

            if ('person' === $row['stat_user_type']) {
                if ('desktop' === $row['stat_participant_device']) {
                    $this->addTotalData($windows_all, 'Total to users');
                    $this->addTotalData($windows_all, 'total');
                    if ('Windows' === $row['stat_participant_os']) {
                        if (array_key_exists($row['stat_participant_browser'], $this->settings->getSettings('connections'))) {
                            $this->addTotalData($row['stat_participant_browser'], 'Total to users');
                            $this->addTotalData($row['stat_participant_browser'], 'total');
                        } else {
                            $this->addTotalData($windows_undefined, 'Total to users');
                            $this->addTotalData($windows_undefined, 'total');
                        }
                    } else {
                        $this->addTotalData($windows_undefined, 'Total to users');
                        $this->addTotalData($windows_undefined, 'total');
                    }
                } elseif ('mobile' === $row['stat_participant_device']) {
                    $this->addTotalData($mobile_all, 'Total to users');
                    $this->addTotalData($mobile_all, 'total');
                    if ('Android' === $row['stat_participant_os']) {
                        $this->addTotalData($android, 'Total to users');
                        $this->addTotalData($android, 'total');
                    } else {
                        $this->addTotalData($ios, 'Total to users');
                        $this->addTotalData($ios, 'total');
                    }
                }
            }
            if (1 === $this->settings->getSettings('show_users') && 'person' === $row['stat_user_type']) {
                if ('desktop' === $row['stat_participant_device']) {
                    $this->addTotalData($windows_all, $row['stat_user_id']);
                    if ('Windows' === $row['stat_participant_os']) {
                        if (array_key_exists($row['stat_participant_os'], $this->settings->getSettings('connections'))) {
                            $this->addTotalData($row['stat_participant_os'], $row['stat_user_id']);
                        } else {
                            $this->addTotalData($windows_undefined, $row['stat_user_id']);
                        }
                    } else {
                        $this->addTotalData($windows_undefined, $row['stat_user_id']);
                    }
                } elseif ('mobile' === $row['stat_participant_device']) {
                    $this->addTotalData($mobile_all, $row['stat_user_id']);
                    if ('Android' === $row['stat_participant_os']) {
                        $this->addTotalData($android, $row['stat_user_id']);
                    } else {
                        $this->addTotalData($ios, $row['stat_user_id']);
                    }
                }
            }
        }
    }

    public function get($id)
    {
        return $this->total[$id];
    }

    public function getTotal()
    {
        $this->total;
    }

    private function addTotalData($id, $name)
    {
        $this->total[$id][$name] += 1;
    }
}