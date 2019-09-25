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
    
    /**
     * 获取model相对路径
     * @param type $modelClass
     * @return type
     */
    public static function getModelPath($modelClass)
    {
        return str_replace('App\\', '', $modelClass);
    }
    
    /**
     * 获取表字段的索引名称
     * @param string $table
     * @param string $column_name
     * @return type
     */
    public static function getColumnsIndex(string $table, string $column_name)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $columns = DB::select("SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = '{$db}' AND `TABLE_NAME` = '{$prefix}{$table}' AND `COLUMN_NAME` = '$column_name';");
        return $columns[0]->CONSTRAINT_NAME ?? '';
    }
    
    /**
     * 根据索引名称获取`相同索引名称`字段
     * @param string $table
     * @param string $constraint_name
     * @return type
     */
    public static function getIndexColumns(string $table, string $constraint_name)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $columns = DB::select("SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = '{$db}' AND `TABLE_NAME` = '{$prefix}{$table}' AND `CONSTRAINT_NAME` = '$constraint_name';");
        return $columns;
    }
    
    /**
     * api接口的 url path
     * @param string $name
     * @return type
     */
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
    
    /**
     * Vue的驼峰命名
     * @param string $name
     * @return type
     */
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
    
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public static function getTable($name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        return str_replace(
            '\\', '', Str::snake(Str::plural($class))
        );

    }
    
    /**
     * 获取表主键名
     * @param string $table
     * @return type
     */
    public static function getKeyName(string $table)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $row = DB::select("SELECT column_name FROM INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` WHERE TABLE_SCHEMA = '{$db}' AND table_name='{$prefix}{$table}' AND constraint_name='PRIMARY'");
        
        return $row[0]->column_name ?? null;
    }
    
    public static function existsTable(string $table)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $row = DB::select("SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$db}' AND table_name='{$prefix}{$table}'");
        return $row[0]->table_name ?? null;
    }
    
    /**
     * 获取 laravel model 验证规则
     * @param string $type 表字段类型
     * @return string
     */
    public static function getDataType(string $type)
    {
        $data_type = strtoupper($type);
        $data = [
            'integer' => ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INTEGER', 'INT', 'BIGINT'],
            'numberic' => ['FLOAT', 'DOUBLE', 'DECIMAL'],
            'date' => ['DATE'],
            'time' => ['TIME'],
            'year' => ['YEAR'],
            'datetime' => ['DATETIME', 'TIMESTAMP'],
            'string' => ['CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'LONGTEXT'],
        ];
        if (in_array($data_type, $data['integer'])) {
            return 'integer';
        } elseif (in_array($data_type, $data['numberic'])) {
            return 'numberic';
        } elseif (in_array($data_type, $data['date'])) {
            return 'date';
        } elseif (in_array($data_type, $data['time'])) {
            return 'date_format:H:i:s';
        } elseif (in_array($data_type, $data['year'])) {
            return 'date_format:Y';
        } elseif (in_array($data_type, $data['datetime'])) {
            return 'datetime';
        } else {
            return 'string';
        }
    }
    
    /**
     * 返回字符串中给定值之前的所有内容
     * @param type $subject
     * @param type $search
     * @return type
     */
    public static function strBefore($subject, $search=null)
    {
        if($search === null){
            $search = [' ',':','：'];
        }
        if(is_string($search)){
            $datas[] = $search;
        }else{
            $datas = $search;
        }
        foreach ($datas as $data){
            $subject = str_before($subject, $data);
        }
        return $subject;
    }
}
