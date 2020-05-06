<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 22.07.2018
 * Time: 15:13
 */

namespace Statistics\StatisticData;

interface InterfaceDate
{
    public function set($day, $date);

    public function get();
}