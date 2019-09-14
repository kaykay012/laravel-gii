<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace kaykay012\laravelgii;

use Illuminate\Support\Facades\DB;

/**
 * Description of CommonClass
 *
 * @author Administrator
 */
class CommonClass
{
    public static function getColumns(string $table)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $columns = DB::select("SELECT "
                        . "COLUMN_NAME,IS_NULLABLE,DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,COLUMN_COMMENT "
                        . "FROM INFORMATION_SCHEMA.COLUMNS "
                        . "WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$prefix}{$table}'"
                        . ";"
        );
        return $columns;
    }
}
