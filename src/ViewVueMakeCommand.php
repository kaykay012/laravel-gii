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
        
        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') || ! $this->option('force'))) {
            if($this->files->exists($form)){
                $this->error($this->type."`{$form}` already exists!");
                $error =1;
            }
            if($this->files->exists($list)){
                $this->error($this->type."`{$list}` already exists!");
                $error =1;                
            }
            if(isset($error)){
                return false;
            }
        }

        $this->makeDirectory($form);
        $this->makeDirectory($list);
        
        $this->files->put($form, $this->buildClass('form'));
        $this->files->put($list, $this->buildClass('list'));

        $api_url_path = CommonClass::getRoutePathName($name);
        $functionName = CommonClass::getVueStudlyCase($name);
        $functionNameLcfirst = lcfirst($functionName);

        $this->info($this->type."`{$form}` created successfully.");
        $this->info($this->type."`{$list}` created successfully.");
        $this->info('');
        
        $this->info("
function {$functionNameLcfirst}List (obj) {
    return request({
      url: '{$api_url_path}/index',
      method: 'GET',
      params: obj
    })
}");
        $this->info("
function edit{$functionName} (obj) {
    return request({
      url: '{$api_url_path}/update',
      method: 'POST',
      params: obj
    })
}");
        $this->info('');
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
        // coin/ruleAward
        $input_path = $this->getNameInput();
        $pathName = CommonClass::getVueStudlyCase($input_path);
        $replace['DummyInputPath'] = $input_path;
        $replace['DummyPathNameTitleCase'] = $pathName;
        
        if ($this->option('table')) {
            $table = $this->option('table');
        }
        do{
            $exists = CommonClass::existsTable($table);
            if (!$exists) {
                $tableAsk = $this->ask("The table `{$table}` does not exist. Enter table name to regenerate or exit.",'Quit');
                if(strtolower($tableAsk) === 'quit' || strtolower($tableAsk) === 'q'){
                    exit(0);
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
        $str_search = $str_list = $str = "";
        $n = 1;
        foreach($columns as $column){
            if($primaryKeyName === $column->COLUMN_NAME){
                continue;
            }
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            if($n > 2){
                $str .='
        <!-- ';
            }else{
                $str .="
        ";
            }
            $str .= "<el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-input v-model=\"searchData.{$column->COLUMN_NAME}\" placeholder></el-input>
        </el-form-item>";
            if($n > 2){
                $str .=' -->';
            }
            
            $str_list .= "
        <el-table-column label=\"{$COLUMN_COMMENT}\" prop=\"{$column->COLUMN_NAME}\" align=\"center\"></el-table-column>";
            
            if($n > 1){
                $str_search .=  ",
        ";
            }else{
                $str_search .=  "
        ";
            }
            $str_search .= "{$column->COLUMN_NAME}: '{$column->COLUMN_DEFAULT}'";
            
            ++$n;
        }
        // ------------------------------------------
        
        return array_merge($replace, [
            'DummySearchInput' => $str,
            'DummyList' => $str_list,
            'DummySearchParams' => $str_search,
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
        return CommonClass::getKeyName($table);
    }
    
    public function existsTable(string $table)
    {
        return CommonClass::existsTable($table);
    }
    
    protected function getStub(){}
    
    protected function getView($name)
    {
        return __DIR__."/view-vue/{$name}.vue";
    }
}
