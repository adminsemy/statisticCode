<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 24.07.2018
 * Time: 18:28
 */

namespace Statistics\StatisticData;

interface InterfaceTotalVertical
{
    public function sumDataMember($type, $id, $data = 1);

    public function get($id);
}