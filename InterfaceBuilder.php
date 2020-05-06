<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 20.07.2018
 * Time: 22:14
 */

namespace Statistics;

interface InterfaceBuilder
{
    public function getCompanies();

    public function getGeneral();

    public function getDetailed();

    public function getConnections();
}