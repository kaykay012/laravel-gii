<?php

namespace kaykay012\laravelgii;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class WikiMakeCommand extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:wiki';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create api wiki.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'wiki';
    
    private $columns;
    private $url_path_index;
    private $url_path_show;
    private $url_path_create;
    private $url_path_update;
    private $url_path_destroy;

    public function handle()
    {
        if (! $this->option('force')) {
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
        
        $this->createWiki();
        
        $this->info('Api Wiki created successfully.');
    }
    
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('parent')) {
            return __DIR__ . '/stubs/controller.nested.stub';
        } elseif ($this->option('model')) {
            return __DIR__ . '/stubs/controller.model.stub';
        } elseif ($this->option('resource')) {
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

        $replace = [];

        if ($this->option('parent')) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->option('model')) {
            $replace = $this->buildModelReplacements($replace);
            $replace = $this->buildCurdReplacements($replace);
        }

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
                array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
    
    protected function createWiki()
    {
        $input_name = $this->getNameInput();
        $name = $this->qualifyClass($input_name);
        $url_path = CommonClass::getRoutePathName($name);
        
        $modelClass = $this->parseModel($this->option('model'));
        
        $obj = new $modelClass();
        $table = $obj->getTable();
        $primaryKeyName = $obj->getKeyName();
        $this->columns = $columns = CommonClass::getColumns($table);
        
        $path = base_path() . "/.wiki/{$url_path}";
        
        $this->makeDirectory($path.'/a.wiki');
        
        $this->files->put($path . "/create.wiki", $this->buildCreateClass('create'));
        $this->files->put($path . '/update.wiki', $this->buildUpdateClass('update'));
        $this->files->put($path . '/destroy.wiki', $this->buildDestroyClass('destroy'));
        $this->files->put($path . '/index.wiki', $this->buildIndexClass('index'));
        $this->files->put($path . '/show.wiki', $this->buildShowClass('show'));
    }
    
    protected function buildCreateClass($name)
    {
        $replace['DummyCreateWikiDate'] = date('Y-m-d');
        $replace['DummyHostPath'] = $this->url_path_create;
        
        $str = '';
        foreach($this->columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $IS_NULLABLE = $column->IS_NULLABLE === 'NO' ? '是' : '否';
            $DATA_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            $str .= "\n|{$column->COLUMN_NAME} |{$IS_NULLABLE}  |{$DATA_TYPE} |{$COLUMN_COMMENT}   |";
        }
        $comments = collect($this->columns)->keyBy('COLUMN_NAME')->all();
        $row = $this->getRowData();
        $str2 = '';
        foreach ($row as $key=>$value){
            $str2 .= "
        \"{$key}\": \"{$value}\", //{$comments[$key]->COLUMN_COMMENT}";
        }
        $replace['DummyFormData'] = $str;
        $replace['DummyRowDetail'] = $str2;
        
        $view = $this->files->get($this->getWikiView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
        );
    }
    protected function buildUpdateClass($name)
    {
        $replace['DummyCreateWikiDate'] = date('Y-m-d');
        $replace['DummyHostPath'] = $this->url_path_update;
        
        $str = '';
        foreach($this->columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $IS_NULLABLE = $column->IS_NULLABLE === 'NO' ? '是' : '否';
            $DATA_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            $str .= "\n|{$column->COLUMN_NAME} |{$IS_NULLABLE}  |{$DATA_TYPE} |{$COLUMN_COMMENT}   |";
        }
        $comments = collect($this->columns)->keyBy('COLUMN_NAME')->all();
        $row = $this->getRowData();
        $str2 = '';
        foreach ($row as $key=>$value){
            $str2 .= "
        \"{$key}\": \"{$value}\", //{$comments[$key]->COLUMN_COMMENT}";
        }
        $replace['DummyFormData'] = $str;
        $replace['DummyRowDetail'] = $str2;
        
        $view = $this->files->get($this->getWikiView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
        );
    }
    protected function buildShowClass($name)
    {
        $replace['DummyCreateWikiDate'] = date('Y-m-d');
        $replace['DummyHostPath'] = $this->url_path_show;
        
        $str = '';
        foreach($this->columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $IS_NULLABLE = $column->IS_NULLABLE === 'NO' ? '是' : '否';
            $DATA_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            $str .= "\n|{$column->COLUMN_NAME} |{$IS_NULLABLE}  |{$DATA_TYPE} |{$COLUMN_COMMENT}   |";
        }
        $comments = collect($this->columns)->keyBy('COLUMN_NAME')->all();
        $replace['DummyFormData'] = $comments['id']->COLUMN_COMMENT;
        $row = $this->getRowData();
        $str2 = '';
        foreach ($row as $key=>$value){
            $str2 .= "
        \"{$key}\": \"{$value}\", //{$comments[$key]->COLUMN_COMMENT}";
        }
        $replace['DummyRowDetail'] = $str2;
        
        $view = $this->files->get($this->getWikiView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
        );
    }
    protected function buildIndexClass($name)
    {
        $replace['DummyCreateWikiDate'] = date('Y-m-d');
        $replace['DummyHostPath'] = $this->url_path_index;
        
        $str = '';
        foreach($this->columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $IS_NULLABLE = '否';
            $DATA_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            $str .= "\n|{$column->COLUMN_NAME} |{$IS_NULLABLE}  |{$DATA_TYPE} |{$COLUMN_COMMENT}   |";
        }
        $replace['DummyFormData'] = $str;
        
        $comments = collect($this->columns)->keyBy('COLUMN_NAME')->all();
        $rows = $this->getRowsData();
        $str2 = '';
        foreach ($rows as $rk=>$row){
            $str2 .= '
            {';
            foreach($row as $key=>$value){
            $str2 .= "
                \"{$key}\": \"{$value}\",";
                if($rk ==0){
                    $str2 .= " //{$comments[$key]->COLUMN_COMMENT}";
                }
            }
            
            $str2 .= '
            },';            
        }

        $replace['DummyRowDetail'] = $str2;
        
        $view = $this->files->get($this->getWikiView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
        );
    }
    protected function buildDestroyClass($name)
    {
        $replace['DummyCreateWikiDate'] = date('Y-m-d');
        $replace['DummyHostPath'] = $this->url_path_destroy;
        
        $comments = collect($this->columns)->keyBy('COLUMN_NAME')->all();
        $replace['DummyFormData'] = $comments['id']->COLUMN_COMMENT;
        
        $view = $this->files->get($this->getWikiView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
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
            $this->error('model does not exist.');
            exit(0);
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
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
        ];
    }

    protected function buildCurdReplacements(array $replace)
    {
        $modelClass = $this->parseModel($this->option('model'));
        
        $obj = new $modelClass();
        $table = $obj->getTable();
        $primaryKeyName = $obj->getKeyName();
        $this->columns = $columns = CommonClass::getColumns($table);
        
        // Search Condition
        $uniqueRuleUpdate = $uniqueRule = $searchCondition = '';
        foreach ($columns as $key=>$column) {
            $searchCondition .= '
        if ($request->'.$column->COLUMN_NAME.') {
            $model->where(\''.$column->COLUMN_NAME.'\', $request->'.$column->COLUMN_NAME.');
        }';
            //单字段唯一索引
            if($column->COLUMN_KEY === 'UNI'){
                $uniqueRule .= "
                '{$column->COLUMN_NAME}' => ['unique:{$table}'],";
            
                $uniqueRuleUpdate .= "
                '".$column->COLUMN_NAME."' => [Rule::unique('".$table."')->ignore(".'$request->id'.")],";
            }
            //多字段唯一索引
            if($column->COLUMN_KEY === 'MUL'){
                $constraint_name = CommonClass::getColumnsIndex($table, $column->COLUMN_NAME);
                $uniques = CommonClass::getIndexColumns($table, $constraint_name);
                $fields = collect($uniques)->pluck('COLUMN_NAME');
                
                foreach ($fields as $field){
                    
                    $uniqueRule .= "
                '".$field."' => [Rule::unique('".$table."')";
                    
                    $fields_except = $fields->reject(function ($value, $key) use($field) {
                        return $value === $field;
                    });
                    $where_str = '';
                    foreach($fields_except as $fe){
                        $where_str .= '->where("'.$fe.'", $request->'.$fe.'?:" ")';
                    }
                    
                    $uniqueRule .= $where_str.'],';
                    
                    //===========================
                    
                    $uniqueRuleUpdate .= "
                '".$field."' => [Rule::unique('".$table."')";
                    
                    $fields_except = $fields->reject(function ($value, $key) use($field) {
                        return $value === $field;
                    });
                    $where_str = '';
                    foreach($fields_except as $fe){
                        $where_str .= '->where("'.$fe.'", $request->'.$fe.'?:" ")';
                    }
                    
                    $uniqueRuleUpdate .= $where_str . "->ignore(".'$request->id'.")],";
                }
            }
            
        }
        
        return array_merge($replace, [
            'DummyTableName' => $table,
            'DummySearchCondition' => ltrim($searchCondition),
            'DummyPrimaryKeyName' => $primaryKeyName,
            'DummyUniqueRule' => $uniqueRule,
            'DummyUniqueUpdateRule' => $uniqueRuleUpdate,
        ]);
    }
    
    protected function getWikiView($name)
    {
        return __DIR__."/wiki/{$name}.wiki";
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
