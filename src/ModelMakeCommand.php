<?php

namespace kaykay012\laravelgii;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\DB;

class ModelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:modelk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class with rules';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

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
        if ($this->option('table')) {
            $table = $this->option('table');
        }else{
            $table = $this->getTable($name);
        }
        
        do{
            $exists = $this->existsTable($table);
            if (!$exists) {
                $tableAsk = $this->ask("The table `{$table}` does not exist. Enter table name to regenerate or continue generate it.", $table);                
                if($tableAsk === $table){
                    break;
                }else{
                    $table = $tableAsk;
                }
            }else{
                break;
            }
        }while(isset($tableAsk) && $tableAsk !== false);
        
        $replace['DummyTableName'] = $table;
        
        $replace = $this->buildRulesReplacements($replace, $table);
        $replace = $this->buildAttributesReplacements($replace, $table);
        
        return str_replace(
                array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }
        
        if ($this->option('controller') || $this->option('resource')) {
            $this->createController();
        }
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory()
    {
        $this->call('make:factory', [
            'name' => $this->argument('name').'Factory',
            '--model' => $this->argument('name'),
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a controller for the model.
     *
     * @return void
     */
    protected function createController()
    {
        $controller = Str::studly(class_basename($this->argument('name')));

        $modelName = $this->qualifyClass($this->getNameInput());

        $this->call('make:controller', [
            'name' => "{$controller}Controller",
            '--model' => $this->option('resource') ? $modelName : null,
        ]);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            return __DIR__.'/stubs/pivot.model.stub';
        }

        return __DIR__.'/stubs/model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, factory, and resource controller for the model'],

            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],

            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],

            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],

            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model.'],

            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model.'],

            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller.'],
            
            ['table', 't', InputOption::VALUE_OPTIONAL, 'Generate the model with table name.'],
        ];
    }
    
    protected function buildRulesReplacements(array $replace, $table)
    {
        $columns = CommonClass::getColumns($table);
        $primaryKeyName = $this->getKeyName($table);
        
        // rules -------------------------------------
        $str = '';
        foreach ($columns as $column) {
            if($primaryKeyName === $column->COLUMN_NAME){
                continue;
            }
            $str .= "
            '{$column->COLUMN_NAME}' => ";
            $str .= "[";
            if ($column->IS_NULLABLE === 'NO') {
                $str .= "'required', ";
            }
            
            $str .= "'{$this->getDataType($column->DATA_TYPE)}'";
            
            if ($column->CHARACTER_MAXIMUM_LENGTH) {
                $str .= ", 'max:{$column->CHARACTER_MAXIMUM_LENGTH}'";
            }
            
            $str .= "],";
        }
        // ------------------------------
        
        return array_merge($replace, [
            'DummyRules' => $str,
        ]);
    }
    
    protected function buildAttributesReplacements(array $replace, $table)
    {
        $columns = CommonClass::getColumns($table);
        
        // attributes ----------------------------------
        $str = "";
        foreach($columns as $column){
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $str .= "
            '{$column->COLUMN_NAME}' => '{$COLUMN_COMMENT}',";
        }
        // ------------------------------------------
        
        return array_merge($replace, [
            'DummyAttributes' => $str,
        ]);
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
    
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    protected function getTable($name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        return str_replace(
            '\\', '', Str::snake(Str::plural($class))
        );

    }
    
    public function getKeyName(string $table)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $row = DB::select("SELECT column_name FROM INFORMATION_SCHEMA.`KEY_COLUMN_USAGE` WHERE TABLE_SCHEMA = '{$db}' AND table_name='{$prefix}{$table}' AND constraint_name='PRIMARY'");
        
        return $row[0]->column_name ?? null;
    }
    
    public function existsTable(string $table)
    {
        $prefix = DB::getConfig('prefix');
        $db = config('database.connections.mysql.database');
        $row = DB::select("SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$db}' AND table_name='{$prefix}{$table}'");
        return $row[0]->table_name ?? null;
    }
}
