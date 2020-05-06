<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 04.08.2018
 * Time: 15:00
 */

namespace Statistics\StatisticData\Detailed;


use Statistics\Converter;
use Statistics\InterfaceNormalizationSettings;
use Statistics\StatisticData\InterfaceBody;
use Statistics\StatisticData\InterfaceHeader;

class BodyHorizontal implements InterfaceBody
{
    const ASC = 'ASC';
    const DESC = 'DESC';
    private $body = [];
    private $settings;
    private $header;
    private $count_person = 0;
    private $count_terminals;
    private $unidentified;

    public function __construct(InterfaceNormalizationSettings $settings, InterfaceHeader $header)
    {
        $this->settings = $settings;
        $this->header = $header;
    }

    public function createBodyHorizontal(array $statistic, array $user, $sort = 'ASC')
    {
        $i = 0;
        $names = [];
        $person = [];
        $terminals = [];
        $users_name = [];

        foreach ($statistic as $rows) {
            if (in_array($rows['stat_user_id'], $users_name))
                continue;
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
                    $users_name[$rows['stat_user_id']] = $name;

                } else {
                    $this->unidentified = true;
                }
            }
            if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $rows['stat_user_type']) && $rows['stat_user_id'] === USER_ID) {
                $users_name[$rows['stat_user_id']] = $rows['stat_user_fname'] . ' ' . $rows['stat_user_lname'];
            }
        }

        if (self::ASC === $sort)
            asort($users_name);
        if (self::DESC === $sort)
            arsort($users_name);
        if (!array_key_exists($user[0]['user_id'], $users_name) && 1 === $this->settings->getSettings('show_detailed'))
            $users_name[$user[0]['user_id']] = $user[0]['user_fname'] . ' ' . $user[0]['user_lname'];
        foreach ($users_name as $id=>$name) {
            $this->body[$id]['device'] = $name;
            foreach ($this->header->getHeader() as $key => $column) {
                if ('total_only' === $this->settings->getSettings('display'))
                    break;
                $this->body[$id]['m ' . $column] = 0;
                $this->body[$id]['d ' . $column] = 0;
                $this->body[$id]['p ' . $column] = 0;
            }
            $this->body[$id]['m ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
            $this->body[$id]['d ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
            $this->body[$id]['p ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        }
        if (true === $this->unidentified) {
            $this->body[0]['device'] = $this->settings->getSettings('dictionary')['stat_unidentified_users'];
            foreach ($this->header->getHeader() as $key => $column) {
                if ('total_only' === $this->settings->getSettings('display'))
                    break;
                $this->body[0]['m ' . $column] = 0;
                $this->body[0]['d ' . $column] = 0;
                $this->body[0]['p ' . $column] = 0;
            }
            $this->body[0]['m ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
            $this->body[0]['d ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
            $this->body[0]['p ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        }

        $this->body['Total to users']['device'] = "{$this->settings->getSettings('dictionary')['stat_total_for_users']} ({$this->count_person})";
        foreach ($this->header->getHeader() as $key => $column) {
            $this->body['Total to users']['m ' . $column] = 0;
            $this->body['Total to users']['d ' . $column] = 0;
            $this->body['Total to users']['p ' . $column] = 0;
        }
        $this->body['Total to users']['m ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total to users']['d ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total to users']['p ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;

        $this->body['Total to terminals']['device'] = "{$this->settings->getSettings('dictionary')['stat_total_for_terminals']} ({$this->count_terminals})";
        foreach ($this->header->getHeader() as $key => $column) {
            $this->body['Total to terminals']['m ' . $column] = 0;
            $this->body['Total to terminals']['d ' . $column] = 0;
            $this->body['Total to terminals']['p ' . $column] = 0;
        }
        $this->body['Total to terminals']['m ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total to terminals']['d ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total to terminals']['p ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;

        $this->body['Total']['device'] = $this->settings->getSettings('dictionary')['stat_total'];
        foreach ($this->header->getHeader() as $key => $column) {
            $this->body['Total']['m ' . $column] = 0;
            $this->body['Total']['d ' . $column] = 0;
            $this->body['Total']['p ' . $column] = 0;
        }
        $this->body['Total']['m ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total']['d ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
        $this->body['Total']['p ' . $this->settings->getSettings('dictionary')['stat_total']] = 0;
    }

    public function fillingData(array $statistic)
    {
        foreach ($statistic as $row) {
            if ('total_only' !== $this->settings->getSettings('display') ) {
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'm ', $this->header->getIdHeader($row['meeting_stat_day']), $row['num_meetings']);
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'd ', $this->header->getIdHeader($row['meeting_stat_day']), Converter::convertToMinute($row['sum_duration']));
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'p ', $this->header->getIdHeader($row['meeting_stat_day']), $row['sum_participants']);
            } else {
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'm ', $this->settings->getSettings('stat_total'), $row['num_meetings']);
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'd ', $this->settings->getSettings('stat_total'), Converter::convertToMinute($row['sum_duration']));
                $this->addHorizontalData($row['stat_user_type'], $row['stat_user_id'], 'p ', $this->settings->getSettings('stat_total'), $row['sum_participants']);
            }
        }
    }

    public function fillingDataTotal(array $total)
    {
        foreach ($total as $row) {
            $column = $this->header->getIdHeader($row['meeting_stat_day']);
            $this->addHorizontalTotalData('m ', $column, 1);
            $this->addHorizontalTotalData('d ', $column,  Converter::convertToMinute($row['stat_meeting_duration']));
            $this->addHorizontalTotalData('p ', $column, $row['sum_participants']);
        }
    }

    public function noTotal()
    {
        unset($this->body['Total to users']);
        unset($this->body['Total']);
    }

    public function convertHorizontalBody()
    {
        $this->body = Converter::convertToHorizontalData($this, $this->settings);
    }

    public function getBody()
    {
        return $this->body;
    }
    
    private function addHorizontalData ($user_type, $user_id, $type, $id, $data)
    {
        if ('person' === $user_type) {
            if ('total_only' !== $this->settings->getSettings('display'))
                $this->body['Total to users'][$type . $id] += $data;
            $this->body['Total to users'][$type . $this->settings->getSettings('dictionary')['stat_total']] += $data;
        }
        if ('end_point' === $user_type) {
            if ('total_only' !== $this->settings->getSettings('display'))
                $this->body['Total to terminals'][$type . $id] += $data;
            $this->body['Total to terminals'][$type . $this->settings->getSettings('dictionary')['stat_total']] += $data;
        }
        if ((1 === $this->settings->getSettings('show_terminals') && 'end_point' === $user_type) || (1 === $this->settings->getSettings('show_users') && 'person' === $user_type)) {
            if ('total_only' !== $this->settings->getSettings('display'))
                $this->body[$user_id][$type . $id] += $data;
            $this->body[$user_id][$type . $this->settings->getSettings('dictionary')['stat_total']] += $data;
        }
        if ((1 === $this->settings->getSettings('show_detailed') && 'person' === $user_type) && $user_id === $this->settings->getSettings('user_id')) {
            if ('total_only' !== $this->settings->getSettings('display'))
                $this->body[$user_id][$type . $id] += $data;
            $this->body[$user_id][$type . $this->settings->getSettings('dictionary')['stat_total']] += $data;
        }
    }

    private function addHorizontalTotalData ($type, $column, $data)
    {
        if (!isset($this->body['Total'][$type . $column]))
            $this->body['Total'][$type . $column] = 0;
        $this->body['Total'][$type . $column] += $data;
        $this->body['Total'][$type . $this->settings->getSettings('dictionary')['stat_total']] += $data;
    }
}