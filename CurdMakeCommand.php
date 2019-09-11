<?php

namespace Illuminate\Routing\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\DB;

class CurdMakeCommand extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:curd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CURD logic.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $path = base_path() . '\vendor/laravel\framework\src\Illuminate\Routing\Console';
        if ($this->option('parent')) {
            return $path . '/stubs/controller.nested.stub';
        } elseif ($this->option('model')) {
            return __DIR__ . '/stubs/controller.model.stub';
        } elseif ($this->option('resource')) {
            return $path . '/stubs/controller.stub';
        }

        return $path . '/stubs/controller.plain.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        if ($this->option('parent')) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
        }

        $replace = $this->buildCurdReplacements($replace);

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
                array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the replacements for a parent controller.
     *
     * @return array
     */
    protected function buildParentReplacements()
    {
        $parentModelClass = $this->parseModel($this->option('parent'));

        if (!class_exists($parentModelClass)) {
            if ($this->confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => $parentModelClass]);
            }
        }

        return [
            'ParentDummyFullModelClass' => $parentModelClass,
            'ParentDummyModelClass' => class_basename($parentModelClass),
            'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
        ];
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        if (!class_exists($modelClass)) {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => $modelClass]);
            }
        }
        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');

        if (!Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace())) {
            $model = $rootNamespace . $model;
        }

        return $model;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
            ['parent', 'p', InputOption::VALUE_OPTIONAL, 'Generate a nested resource controller class.'],
        ];
    }

    protected function buildCurdReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));
        $obj = new $modelClass();
        $table = $obj->getTable();
        $columns = $this->getColumns($table);
        
        // rule -------------------------------------
        $str = "[";
        foreach ($columns as $column) {
            $str .= "\n\t\t'{$column->COLUMN_NAME}' => ";
            $str .= "[";
            if ($column->IS_NULLABLE === 'NO') {
                $str .= "'required', ";
            }
            $str .= "'{$this->getDataType($column->DATA_TYPE)}'";
            $str .= "],";
        }
        $str .= "\n\t],";
        // ------------------------------------------
        
        // custom rule ------------------------------
        $str .= "\n\t[],";
        // ------------------------------------------
        
        // comment ----------------------------------
        $str .= "\n\t[";
        foreach($columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $str .= "\n\t\t'{$column->COLUMN_NAME}' => '{$COLUMN_COMMENT}',";
        }
        $str .= "\n\t]";
        // ------------------------------------------
        
        return array_merge($replace, [
            'DummyRules' => $str,
        ]);
    }

    protected function getColumns(string $table)
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

    protected function getDataType(string $type)
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

}
