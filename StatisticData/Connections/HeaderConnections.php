<?php

namespace Statistics\StatisticData\Connections;

use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

/**
 * Created by PhpStorm.
 * User: Nsm-Antonov-DA
 * Date: 13.08.2018
 * Time: 10:20
 */

class HeaderConnections implements InterfaceHeader
{
    const ASC = 'ASC';
    const DESC = 'DESC';
    private $header = [];
    private $settings;
    private $unidentified = false;
    private $count_person = 0;
    private $count_terminals = 0;

    public function __construct(InterfaceNormalizationSettings $settings)
    {
        $this->settings = $settings;
    }

    public function setConnectionsData(array $statistic, $sort = 'ASC')
    {
        $i = 0;
        $names = [];
        $person = [];
        $terminals = [];
        foreach ($statistic as $rows) {
            if ('person' === $rows['stat_user_type']) {
                if (!in_array($rows['stat_user_id'], $person)) {
                    $person[] = $rows['stat_user_id'];
                    $this->count_person++;
                }
            }
            if ('end_point' === $rows['stat_user_type']) {
                if (!in_array($rows['stat_user_id'], $terminals)) {
                    $terminals[] = $rows['stat_user_id'];
                    $this->count_terminals++;
                }
            }
            if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $rows['stat_user_type']) ||
                (1 === $this->settings->getSettings('show_users') && 'person' == $rows['stat_user_type'])) {
                if (0 != $rows['stat_user_id']) {
                    $name = $rows['stat_user_fname'] . ' ' . $rows['stat_user_lname'];
                    $name = trim($name);
                    if (!($rows['stat_user_fname'] > '') && !isset($names[$rows['stat_user_id']]['id'])) {
                        $names[$rows['stat_user_id']]['Name'] = 'Noname ' . $i++;
                        $names[$rows['stat_user_id']]['id'] = $rows['stat_user_id'];
                        $name = $names[$rows['stat_user_id']]['Name'];
                    }
                    if (!($rows['stat_user_fname'] > '') && isset($names[$rows['stat_user_id']]['id'])) {
                        $name = $names[$rows['stat_user_id']]['Name'];
                    }
                    $this->header[$rows['stat_user_id']] = $name;
                } else {
                    $this->unidentified = true;
                }
            }
            if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $rows['stat_user_type']) && $rows['stat_user_id'] === USER_ID) {
                $this->header[$rows['stat_user_id']] = $rows['stat_user_fname'] . ' ' . $rows['stat_user_lname'];
            }
        }
        if (self::ASC === $sort)
            asort($this->header);
        if (self::DESC === $sort)
            arsort($this->header);
        if (true === $this->unidentified)
            $this->header[0] = $this->settings->getSettings('dictionary')['stat_unidentified_users'];
        $this->header['Total to users'] = "{$this->settings->getSettings('dictionary')['stat_total_for_users']} ({$this->count_person})";
        $this->header['Total to terminals'] = "{$this->settings->getSettings('dictionary')['stat_total_for_terminals']} ({$this->count_terminals})";
    }

    public function noTotal()
    {
        unset($this->header['Total to users']);
        unset($this->header['Total to terminals']);
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