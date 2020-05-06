<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 07.07.2018
 * Time: 22:34
 */

namespace Statistics\DB;


class DBQueriesMySQL implements InterfaceDBQuery
{
    private $db_query = [];

    public function queryDB($name_query, $query)
    {
        $this->db_query[$name_query] = $query;
    }

    public function getQuery($name)
    {
        return $this->db_query[$name];
    }
}