<?php

namespace kaykay012\laravelgii;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

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
    
    protected $table;
    protected $ignore_fields = ['created_at', 'updated_at', 'deleted_at'];

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

        if ($this->option('table')) {
            $table = $this->option('table');
        }
        do{
            $exists = CommonClass::existsTable($table);
            if (!$exists) {
                $tableAsk = $this->ask("The table `{$table}` does not exist. Enter table name to regenerate or exit.",'Quit');
                if($tableAsk === 'Quit'){
                    exit(0);
                }else{
                    $table = $tableAsk;
                }
            }else{
                break;
            }
        }while(isset($tableAsk) && $tableAsk !== false);
        
        $this->table = $table;
        
        $this->makeDirectory($form);
        $this->makeDirectory($list);
        
        $this->files->put($form, $this->buildClass('form'));
        $this->files->put($list, $this->buildClass('list'));

        $api_url_path = CommonClass::getRoutePathName($name);
        $functionName = CommonClass::getVueStudlyCase($name);
        $functionNameLcfirst = lcfirst($functionName);

        $this->info($this->type."`{$form}` created successfully.");
        $this->info($this->type."`{$list}` created successfully.");
        
        
        if($this->hasOption('apiPre')){
            $apiPre = $this->option('apiPre').'/';
        }else{
            $apiPre = '';
        }
        
        $this->info("
function {$functionNameLcfirst}List (obj) {
    return request({
      url: '{$apiPre}{$api_url_path}/index',
      method: 'GET',
      params: obj
    })
}");
      
        $this->info("
function create{$functionName} (obj) {
    return request({
      url: '{$apiPre}{$api_url_path}/create',
      method: 'POST',
      data: obj
    })
}");
      
        $this->info("
function edit{$functionName} (obj) {
    return request({
      url: '{$apiPre}{$api_url_path}/update',
      method: 'POST',
      data: obj
    })
}");
        
        $this->info("
function {$functionNameLcfirst}List (obj, ext = false, exportExcel = false) {
  var data = {
    url: '{$apiPre}{$api_url_path}/index',
    method: 'GET',
    params: obj
  }
  if (ext !== false) {
    Object.assign(data, ext)
  }
  if (exportExcel !== false) {
    Object.assign(data, { responseType: 'blob' })
    Object.assign(data, ext)
  }
  return request(data)
}");
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
        $inputName = $this->getNameInput();
        $table = $this->table;
        $pathName = CommonClass::getVueStudlyCase($inputName);
        $replace['DummyInputPath'] = $inputName;
        $replace['DummyPathNameTitleCase'] = $pathName;
        $replace['DummyPathNameLcfirstTitleCase'] = lcfirst($pathName);//首字母小写
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
            ['radio', null, InputOption::VALUE_OPTIONAL, 'Generate radio input.'],
            ['checkbox', null, InputOption::VALUE_OPTIONAL, 'Generate checkbox input.'],
            ['select', null, InputOption::VALUE_OPTIONAL, 'Generate select input.'],
            ['cut', null, InputOption::VALUE_NONE, '缩减`字段注释`(自动删除空格/冒号后面的字符).'],
            ['apiPre', null, InputOption::VALUE_OPTIONAL, 'API url 前缀'],
        ];
    }
    
    protected function buildRulesReplacements(array $replace, $table)
    {
        $columns = CommonClass::getColumns($table);
        $primaryKeyName = $this->getKeyName($table);
        if ($this->option('radio')) {
            $filedsRadio = explode(',',$this->option('radio'));
        }
        if ($this->option('checkbox')) {
            $filedsCheckbox = explode(',',$this->option('checkbox'));
        }
        if ($this->option('select')) {
            $filedsSelect = explode(',',$this->option('select'));
        }
        // rules -------------------------------------
        $str_input = $str = '';
        foreach ($columns as $column) {
            if($primaryKeyName === $column->COLUMN_NAME){
                continue;
            }
            if(in_array($column->COLUMN_NAME, $this->ignore_fields)){
                continue;
            }
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            if ($this->option('cut')) {
                $COLUMN_COMMENT = CommonClass::strBefore($COLUMN_COMMENT);
            }
            $COLUMN_TYPE = CommonClass::getDataType($column->DATA_TYPE);
            $COLUMN_DEFAULT = in_array($COLUMN_TYPE, ['integer','numberic']) ? $column->COLUMN_DEFAULT : "'{$column->COLUMN_DEFAULT}'";
            $modifier = '';//修饰符
            if(in_array($COLUMN_TYPE, ['integer','numberic'])){
                $modifier = '.number';
            }
            
            /*
             * Form Input default value
             */
            $str_input .= "
        {$column->COLUMN_NAME}: {$COLUMN_DEFAULT},";
            
            // radio
            if(isset($filedsRadio) && in_array($column->COLUMN_NAME, $filedsRadio)){
                $str .= "
        <el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-radio-group v-model=\"inputForm.{$column->COLUMN_NAME}\">
          <el-radio :label=\"1\">备选项1</el-radio>
          <el-radio :label=\"2\">备选项2</el-radio>
          </el-radio-group>
        </el-form-item>";
                continue;
            }
            // checkbox
            if(isset($filedsCheckbox) && in_array($column->COLUMN_NAME, $filedsCheckbox)){
                $str .= "
        <el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-checkbox-group v-model=\"inputForm.{$column->COLUMN_NAME}\">
          <el-checkbox :label=\"1\">复选框 1</el-checkbox>
          <el-checkbox :label=\"2\">复选框 2</el-checkbox>
          </el-checkbox-group>
        </el-form-item>";
                continue;
            }
            // select
            if(isset($filedsSelect) && in_array($column->COLUMN_NAME, $filedsSelect)){
                $str .= "
        <el-select v-model{$modifier}=\"inputForm.{$column->COLUMN_NAME}\" clearable placeholder=\"请选择\">
            <el-option
              v-for=\"item in options\"
              :key=\"item.value\"
              :label=\"item.label\"
              :value=\"item.value\">
            </el-option>
        </el-select>";
                continue;
            }
            $str .= "
        <el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-input type=\"text\" v-model{$modifier}=\"inputForm.{$column->COLUMN_NAME}\"></el-input>
        </el-form-item>";
        }
        // ------------------------------
        
        return array_merge($replace, [
            'DummyRules' => $str,
            'DummyInputParams' => rtrim($str_input,','),
        ]);
    }
    
    protected function buildAttributesReplacements(array $replace, $table)
    {
        $columns = CommonClass::getColumns($table);
        $primaryKeyName = $this->getKeyName($table);
        if ($this->option('radio')) {
            $filedsRadio = explode(',',$this->option('radio'));
        }
        // attributes ----------------------------------
        $str_search = $str_list = $str = "";
        $n = 1;
        foreach($columns as $column){
            if($primaryKeyName === $column->COLUMN_NAME){
                continue;
            }
            $COLUMN_COMMENT = $column->COLUMN_COMMENT ?: strtoupper($column->COLUMN_NAME);
            if ($this->option('cut')) {
                $COLUMN_COMMENT = CommonClass::strBefore($COLUMN_COMMENT, [' ',':','：']);
            }
            
            $html_comment_begin = $html_comment_end = '';
            if($n > 2){
                $html_comment_begin = '<!-- ';
                $html_comment_end = ' -->';
            }
            
            /*
             * search Form Input
             */
            $str .= "
        {$html_comment_begin}<el-form-item label=\"{$COLUMN_COMMENT}\">
          <el-input v-model=\"searchData.{$column->COLUMN_NAME}\" placeholder></el-input>
        </el-form-item>{$html_comment_end}";
            
            /*
             *  table list
             */
            $str_list .= "
        <el-table-column label=\"{$COLUMN_COMMENT}\" prop=\"{$column->COLUMN_NAME}\" align=\"center\">";
            // radio
            if(isset($filedsRadio) && in_array($column->COLUMN_NAME, $filedsRadio)){
                $str_list .= "
          <template slot-scope=\"scope\">
            <span v-if=\"scope.row.{$column->COLUMN_NAME} === 1\">备选项1</span>
            <span v-else>备选项2</span>
          </template>";
            }
            $str_list .= "
        </el-table-column>";
            
            /*
             * Search Params
             */
            $str_search .= "
        {$column->COLUMN_NAME}: null,";
            
            ++$n;
        }
        // ------------------------------------------
        return array_merge($replace, [
            'DummySearchInput' => $str,
            'DummyList' => $str_list,
            'DummySearchParams' => rtrim($str_search,','),
        ]);
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
