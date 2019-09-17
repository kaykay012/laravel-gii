<?php

namespace kaykay012\laravelgii;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\DB;

class ViewVueMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:view-vue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new vue view.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'VUE View';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = $this->getNameInput();
        
        $path = base_path() . '/.view-vue/' .  $name;
        
        $form = $path  . '/form.vue';
        $list = $path  . '/list.vue';

        $this->makeDirectory($form);
        $this->makeDirectory($list);
        
        $this->files->put($form, $this->buildClass('form'));
        $this->files->put($list, $this->buildClass('list'));

        $this->info($this->type.' created successfully.');
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
        $input_path = $this->getNameInput();
        $underline_name = str_replace('/', ' ', $input_path);
        $pathName = title_case($underline_name);    
        $replace['DummyInputPath'] = $input_path;
        $replace['DummyPathNameTitleCase'] = str_replace(' ','',$pathName);
        
        if ($this->option('table')) {
            $table = $this->option('table');
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
        
        $view = $this->files->get($this->getView($name));
        
        return str_replace(
                array_keys($replace), array_values($replace), $view
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],

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
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $str .= "
        <el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-input type=\"text\" v-model=\"inputForm.{$column->COLUMN_NAME}\"></el-input>
        </el-form-item>";
        }
        // ------------------------------
        
        return array_merge($replace, [
            'DummyRules' => $str,
        ]);
    }
    
    protected function buildAttributesReplacements(array $replace, $table)
    {
        $columns = CommonClass::getColumns($table);
        $primaryKeyName = $this->getKeyName($table);
        
        // attributes ----------------------------------
        $str_list = $str = "";
        $n = 1;
        foreach($columns as $column){
            if($primaryKeyName === $column->COLUMN_NAME){
                continue;
            }
            if($n > 2){
                $str .='
        <!-- ';
            }
            
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            $str .= "
        <el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-input v-model=\"searchData.{$column->COLUMN_NAME}\" placeholder></el-input>
        </el-form-item>";
            if($n > 3){
                $str .=' -->';
            }
            
            $str_list .= "
        <el-table-column label=\"{$COLUMN_COMMENT}\" prop=\"{$column->COLUMN_NAME}\" align=\"center\"></el-table-column>";
            
            ++$n;
        }
        // ------------------------------------------
        
        return array_merge($replace, [
            'DummyAttributes' => $str,
            'DummyList' => $str_list,
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
    
    protected function getStub(){}
    
    protected function getView($name)
    {
        return __DIR__."/view-vue/{$name}.vue";
    }
}