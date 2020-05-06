<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 12.10.2018
 * Time: 10:56
 */

namespace Statistics;

interface InterfaceFormatData
{
    /**
     * @return mixed
     */
    public function getFormat();
    public function setArrayData($data, $name = '');
}