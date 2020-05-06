<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 13.07.2018
 * Time: 18:06
 */

namespace Statistics;

interface InterfaceNormalizationSettings
{
    public function setSettings($id, $name);
    public function getSettings($name);
}