<?php

namespace kaykay012\laravelgii;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

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
    private $columns;
    private $url_path_index;
    private $url_path_show;
    private $url_path_create;
    private $url_path_update;
    private $url_path_destroy;

    public function handle()
    {
        if (parent::handle() === false && !$this->option('force'))
        {
            return;
        }
        $input_name = $this->getNameInput();
        $name = $this->qualifyClass($input_name);
        $url_path = CommonClass::getRoutePathName($name);
        $controller_name = str_replace('/', '\\', $input_name);

        $this->url_path_index = "{$url_path}/index";
        $this->url_path_show = "{$url_path}/show";
        $this->url_path_create = "{$url_path}/create";
        $this->url_path_update = "{$url_path}/update";
        $this->url_path_destroy = "{$url_path}/destroy";

        $index = "Route::any('{$this->url_path_index}', '{$controller_name}@index');";
        $show = "Route::any('{$this->url_path_show}', '{$controller_name}@show');";
        $create = "Route::any('{$this->url_path_create}', '{$controller_name}@create');";
        $update = "Route::any('{$this->url_path_update}', '{$controller_name}@update');";
        $destroy = "Route::any('{$this->url_path_destroy}', '{$controller_name}@destroy');";

        $route_admin = base_path('routes/admin.php');
        if (file_exists($route_admin))
        {
            $route_str = "\n{$index}\n{$show}\n{$create}\n{$update}\n{$destroy}\n";
            //file_put_contents($route_admin, $route_str, FILE_APPEND);
        }

        $this->info('');
        $this->info($index);
        $this->info($show);
        $this->info($create);
        $this->info($update);
        $this->info($destroy);
        $this->info("\n");

        //postman 参数
        $str = '';
        $str_json = [];
        foreach ($this->columns as $key => $column)
        {
            $str .= "{$column->COLUMN_NAME}:\n";
            $str_json[$column->COLUMN_NAME] = "";
        }

        $this->info($str);
        $this->info(json_encode($str_json));


        if ($this->option('blade'))
        {
            $modelClass = $this->parseModel($this->option('model'));
            $this->info("\n");
            $params = ['name' => $url_path, '--table' => (new $modelClass())->getTable()];
            if ($this->option('force'))
            {
                $params = array_merge($params, ['--force' => '1']);
            }
            $this->call('make:view-blade', $params);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('parent'))
        {
            return __DIR__ . '/stubs/controller.nested.stub';
        } elseif ($this->option('model'))
        {
            if ($this->option('blade'))
            {
                return __DIR__ . '/stubs/controller.model.blade.stub';
            } else
            {
                return __DIR__ . '/stubs/controller.model.stub';
            }
        } elseif ($this->option('resource'))
        {
            return __DIR__ . '/stubs/controller.stub';
        }

        return __DIR__ . '/stubs/controller.plain.stub';
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
        $class = str_replace($controllerNamespace . '\\', '', $name);

        $replace = [];

        if ($this->option('parent'))
        {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model'))
        {
            $replace = $this->buildModelReplacements($replace);
            $replace = $this->buildCurdReplacements($replace);
            $replace['DummyViewBladeFolder'] = lcfirst(str_replace('Controller', '', $class));
        }

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
        if (!class_exists($parentModelClass))
        {
            if ($this->confirm("A {$parentModelClass} model does not exist. Do you want to generate it?", true))
            {
                $params = ['name' => $parentModelClass];
                if ($this->option('force'))
                {
                    $params = array_merge($params, ['--force' => '1']);
                }
                $this->call('make:model', $params);
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

        if (!class_exists($modelClass))
        {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true))
            {
                $params = ['name' => $modelClass, '--cut' => true];
                if ($this->option('force'))
                {
                    $params = array_merge($params, ['--force' => '1']);
                }
                $this->call('make:model-rule', $params);
                $path = CommonClass::getModelPath($modelClass);
                require_once base_path() . '/app/' . $path . '.php';
            } else
            {
                exit(0);
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
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model))
        {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');

        if (!Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace()))
        {
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
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
            ['blade', null, InputOption::VALUE_NONE, 'Create controller for view blade'],
            ['connection', null, InputOption::VALUE_OPTIONAL, '数据库连接'],
        ];
    }

    protected function buildCurdReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));

        $obj = new $modelClass();
        $table = $obj->getTable();
        $primaryKeyName = $obj->getKeyName();
        $this->columns = $columns = CommonClass::getColumns($table,$obj->getConnectionName());

        // Search Condition
        $createDefaultValue = $uniqueRuleUpdate = $uniqueRule = $searchCondition = '';
        foreach ($columns as $key => $column)
        {
            $DATA_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            if ($column->COLUMN_NAME == 'created_at')
            {
                $searchCondition .= '
        if ($request->created_at_begin && $request->created_at_end) {
            $model->whereBetween(\'' . $column->COLUMN_NAME . '\', [$request->created_at_begin, Carbon::parse($request->created_at_end)->endOfDay()]);
        }';
            } elseif ($column->COLUMN_NAME == 'updated_at')
            {
                
            } elseif ($column->COLUMN_NAME == 'deleted_at')
            {
                
            } else
            {
                if ($DATA_TYPE == 'string')
                {
                    $searchCondition .= '
        if ($request->' . $column->COLUMN_NAME . ') {
            $model->where(\'' . $column->COLUMN_NAME . '\', \'like\', "%{$request->' . $column->COLUMN_NAME . '}%");
        }';
                } else
                {
                    $searchCondition .= '
        if ($request->' . $column->COLUMN_NAME . ') {
            $model->where(\'' . $column->COLUMN_NAME . '\', $request->' . $column->COLUMN_NAME . ');
        }';
                }
            }

            //$createDefaultValue
            if ($column->COLUMN_DEFAULT !== null && $column->COLUMN_DEFAULT !== '')
            {
                $createDefaultValue .= '
        //' . $column->COLUMN_COMMENT . '
        if($request->' . $column->COLUMN_NAME . ' === \'\'){
            $request->merge([\'' . $column->COLUMN_NAME . '\'=>\'' . $column->COLUMN_DEFAULT . '\']);
        }';
            }

            //单字段唯一索引
            if ($column->COLUMN_KEY === 'UNI')
            {
                $uniqueRule .= "
            '{$column->COLUMN_NAME}' => ['unique:{$obj->getConnectionName()}.{$table}'],";

                $uniqueRuleUpdate .= "
            '" . $column->COLUMN_NAME . "' => [Rule::unique('" . ($obj->getConnectionName() . '.') . $table . "')->ignore(" . '$request->id' . ")],";
            }
            //多字段唯一索引
            if ($column->COLUMN_KEY === 'MUL')
            {
                $constraint_name = CommonClass::getColumnsIndex($table, $column->COLUMN_NAME,$obj->getConnectionName());
                $uniques = CommonClass::getIndexColumns($table, $constraint_name,$obj->getConnectionName());
                $fields = collect($uniques)->pluck('COLUMN_NAME');

                foreach ($fields as $field)
                {

                    $uniqueRule .= "
            '" . $field . "' => [Rule::unique('" . ($obj->getConnectionName() . '.') . $table . "')";

                    $fields_except = $fields->reject(function ($value, $key) use($field) {
                        return $value === $field;
                    });
                    $where_str = '';
                    foreach ($fields_except as $fe)
                    {
                        $where_str .= '->where("' . $fe . '", $request->' . $fe . '?:" ")';
                    }

                    $uniqueRule .= $where_str . '],';

                    //===========================

                    $uniqueRuleUpdate .= "
            '" . $field . "' => [Rule::unique('" . ($obj->getConnectionName() . '.') . $table . "')";

                    $fields_except = $fields->reject(function ($value, $key) use($field) {
                        return $value === $field;
                    });
                    $where_str = '';
                    foreach ($fields_except as $fe)
                    {
                        $where_str .= '->where("' . $fe . '", $request->' . $fe . '?:" ")';
                    }

                    $uniqueRuleUpdate .= $where_str . "->ignore(" . '$request->id' . ")],";
                }
            }
        }

        return array_merge($replace, [
            'DummyTableName' => "{$obj->getConnectionName()}.{$table}",
            'DummySearchCondition' => ltrim($searchCondition),
            'DummyPrimaryKeyName' => $primaryKeyName,
            'DummyUniqueRule' => $uniqueRule,
            'DummyUniqueUpdateRule' => $uniqueRuleUpdate,
            'DummyCreateDefaultValue' => $createDefaultValue,
        ]);
    }

    protected function getRowData()
    {
        $modelClass = $this->parseModel($this->option('model'));
        $row = $modelClass::first();
        return $row->toArray();
    }

    protected function getRowsData()
    {
        $modelClass = $this->parseModel($this->option('model'));
        $row = $modelClass::limit(2)->get();
        return $row->toArray();
    }

}
