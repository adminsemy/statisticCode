<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 20.07.2018
 * Time: 17:22
 */

namespace Statistics\StatisticData;

use Statistics\InterfaceBuilder;
use Statistics\InterfaceData;

interface InterfaceHeader
{
    public function getHeader();

    public function getIdHeader($id);
}