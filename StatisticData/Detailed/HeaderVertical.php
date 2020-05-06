<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 15.07.2018
 * Time: 17:13
 */

namespace Statistics\StatisticData\Detailed;


use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceHeader;

class HeaderVertical implements InterfaceHeader
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

    public function setVerticalData(array $statistic, array $user, $sort = 'ASC')
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
        if (!array_key_exists($user[0]['user_id'], $this->header) && 1 === $this->settings->getSettings('show_detailed'))
            $this->header[$user[0]['user_id']] = $user[0]['user_fname'] . ' ' . $user[0]['user_lname'];
        if (true === $this->unidentified)
            $this->header[0] = $this->settings->getSettings('dictionary')['stat_unidentified_users'];
        $this->header['Total to users'] = "{$this->settings->getSettings('dictionary')['stat_total_for_users']} ({$this->count_person})";
        $this->header['Total to terminals'] = "{$this->settings->getSettings('dictionary')['stat_total_for_terminals']} ({$this->count_terminals})";
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