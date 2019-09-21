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
    
    public static function getRoutePathName(string $name)
    {
        $name = str_replace('App\\Http\\Controllers\\', '', $name);
        $name = str_replace('Controller', '', $name);
        $arr = explode('\\', $name);
        foreach($arr as $k=>$r){
            $arr[$k] = kebab_case($r);
        }
        $path_name = join('/', $arr);
        return $path_name;
    }
    
    public static function getVueStudlyCase(string $name)
    {
        $arr = explode('/', $name);
        foreach($arr as $k=>$val){
            $arr[$k] = snake_case($val);
        }
        $underline_name = join('_', $arr);
        $pathName = studly_case($underline_name);
        return $pathName;
    }
}
