<?php
/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 14.08.2018
 * Time: 7:55
 */

namespace Statistics\StatisticData\Connections;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;
use Statistics\StatisticData\InterfaceTotal;

class BodyConnections implements InterfaceBody
{
    private $body = [];
    private $settings;
    private $header;
    private $total;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header, InterfaceTotal $total)
    {
        $this->settings = $settings;
        $this->header = $header;
        $this->total = $total;
    }

    public function createBodyConnection(array $data)
    {
        $h323 = 'H323';
        $sip = 'SIP';
        $other = 'Other';
        $windows_all = 'Windows_all';
        $windows_undefined = 'Windows_undefined';
        $mobile_all = 'Mobile_all';
        $android = 'Android';
        $ios = 'IoS';
        $connections = $this->settings->getSettings('connections');
        foreach ($data as $row) {
            foreach ($connections as $id=>$connection) {
                if (!array_key_exists($row['meeting_stat_day'] . " " . $id, $this->body)) {
                    $num_day_week = $row['meeting_stat_day'];
                    if ('week_day' === $this->settings->getSettings('display') )
                        $num_day_week = $this->settings->getSettings('name_day')[$row['meeting_stat_day']];
                    $this->body[$row['meeting_stat_day'] . " " . $id]['dataRange'] = Converter::weekString($num_day_week, $row['stat_date'], $this->settings);
                    $this->body[$row['meeting_stat_day'] . " " . $id]['category'] = $connection;
                    foreach ($this->header->getHeader() as $key => $column) {
                        $this->body[$row['meeting_stat_day'] . " " . $id][$key] = 0;
                    }
                    $this->body[$row['meeting_stat_day'] . " " . $id]['total'] = 0;
                }
            }

            if ('end_point' === $row['stat_user_type']) {
                if ($row['stat_user_h323'] > '') {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $h323, 'Total to terminals');
                    $this->addBodyData($row['meeting_stat_day'] . " " . $h323, 'total');
                }
                if (!($row['stat_user_h323'] > '') && $row['stat_user_sip'] > '') {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $sip, 'Total to terminals');
                    $this->addBodyData($row['meeting_stat_day'] . " " . $sip, 'total');
                }
            }
            if ('end_point' === $row['stat_user_type'] && 1 === $this->settings->getSettings('show_terminals')) {

                if ($row['stat_user_h323'] > '') {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $h323, $row['stat_user_id']);
                }
                if (!($row['stat_user_h323'] > '') && $row['stat_user_sip'] > '') {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $sip, $row['stat_user_id']);
                }
                if (!($row['stat_user_h323'] > '') && !($row['stat_user_sip'] > '')) {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $other, $row['stat_user_id']);
                }
            }

            if ('person' === $row['stat_user_type']) {
                if ('desktop' === $row['stat_participant_device']) {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $windows_all, 'Total to users');
                    $this->addBodyData($row['meeting_stat_day'] . " " . $windows_all, 'total');
                    if ('Windows' === $row['stat_participant_os']) {
                        if (array_key_exists($row['stat_participant_browser'], $this->settings->getSettings('connections'))) {
                            $this->addBodyData($row['meeting_stat_day'] . " " . $row['stat_participant_browser'], 'Total to users');
                            $this->addBodyData($row['meeting_stat_day'] . " " . $row['stat_participant_browser'], 'total');
                        } else {
                            $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, 'Total to users');
                            $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, 'total');
                        }
                    } else {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, 'Total to users');
                        $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, 'total');
                    }
                } elseif ('mobile' === $row['stat_participant_device']) {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $mobile_all, 'Total to users');
                    $this->addBodyData($row['meeting_stat_day'] . " " . $mobile_all, 'total');
                    if ('Android' === $row['stat_participant_os']) {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $android, 'Total to users');
                        $this->addBodyData($row['meeting_stat_day'] . " " . $android, 'total');
                    } else {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $ios, 'Total to users');
                        $this->addBodyData($row['meeting_stat_day'] . " " . $ios, 'total');
                    }
                }
            }
            if (1 === $this->settings->getSettings('show_users') && 'person' === $row['stat_user_type']) {
                if ('desktop' === $row['stat_participant_device']) {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $windows_all, $row['stat_user_id']);
                    if ('Windows' === $row['stat_participant_browser']) {
                        if (array_key_exists($row['stat_participant_os'], $this->settings->getSettings('connections'))) {
                            $this->addBodyData($row['meeting_stat_day'] . " " . $row['stat_participant_browser'], $row['stat_user_id']);
                        } else {
                            $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, $row['stat_user_id']);
                        }
                    } else {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $windows_undefined, $row['stat_user_id']);
                    }
                } elseif ('mobile' === $row['stat_participant_device']) {
                    $this->addBodyData($row['meeting_stat_day'] . " " . $mobile_all, $row['stat_user_id']);
                    if ('Android' === $row['stat_participant_os']) {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $android, $row['stat_user_id']);
                    } else {
                        $this->addBodyData($row['meeting_stat_day'] . " " . $ios, $row['stat_user_id']);
                    }
                }
            }
        }
        foreach ($connections as $id=>$connection) {
            $this->body['Total - ' . $id] = $this->total->get($id) ? $this->total->get($id) : [];
        }
    }

    public function convertBodyConnections()
    {
        $this->body = Converter::converterToConnections($this, $this->header, $this->settings);
    }

    public function getBody()
    {
        return $this->body;
    }

    private function addBodyData($id, $name)
    {
        if (!isset($this->body[$id][$name]))
            $this->body[$id][$name] = 0;
        $this->body[$id][$name] += 1;
    }
}