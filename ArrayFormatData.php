<?php
/**
 * Created by PhpStorm.
 * User: Антонов Денис
 * Date: 12.10.2018
 * Time: 11:40
 */

namespace Statistics;


class ArrayFormatData implements InterfaceFormatData
{
    private $array_data = [];
    /**
     * @return mixed
     */
    public function getFormat()
    {
        return $this->array_data;
    }

    public function setArrayData($data, $name = '')
    {
        if (empty($name)) {
            $this->array_data = $this->array_data + $data;
        } else {
            $this->array_data[$name] = $data;
        }
    }
}