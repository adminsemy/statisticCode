<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 03.12.2018
 * Time: 15:28
 */

namespace Statistics\StatisticData\VMR;

interface InterfaceNumberVMR
{
    public function getNumberVmr();

    public function getNumberVmrName($id);
}