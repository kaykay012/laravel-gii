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
        $columns = DB::select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$prefix}{$table}'");
        return $columns;
    }
    
    public static function getModelPath($modelClass)
    {
        return str_replace('App\\', '', $modelClass);
    }
    
    public static function getColumnsIndex(string $table, string $column_name)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $columns = DB::select("SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = '{$db}' AND `TABLE_NAME` = '{$prefix}{$table}' AND `COLUMN_NAME` = '$column_name';");
        return $columns[0]->CONSTRAINT_NAME ?? '';
    }
    
    public static function getIndexColumns(string $table, string $constraint_name)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $columns = DB::select("SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = '{$db}' AND `TABLE_NAME` = '{$prefix}{$table}' AND `CONSTRAINT_NAME` = '$constraint_name';");
        return $columns;
    }
}
