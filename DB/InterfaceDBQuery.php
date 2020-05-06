<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 07.07.2018
 * Time: 22:30
 */

namespace Statistics\DB;


interface InterfaceDBQuery
{
    public function queryDB($name_query, $query);
    public function getQuery($name);

}