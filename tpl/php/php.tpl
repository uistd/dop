{{$code_php_tag}}
{{$code_namespace}} {{$main_namespace|php_ns}};

/**
 * Class {{$class_name}}
 * @package {{$main_namespace|php_ns}}
 */
class {{$class_name}}
{
{{foreach $struct.item_list as $name => $item}}
    /**
     * @var {{$item|php_var_type}}{{if $item->note}} {{$item->note}}{{/if}}
     */
    public ${{$name}}{{if null !== $item->default}} = {{$item->default}}{{/if}};
    
{{/foreach}}
    /**
    * 初始化数据
    * @param array $data
    * @return void
    */
    public function init($data)
    {
    {{foreach $struct.item_list as $name => $item}}
    {{php_item_int var_name=$name rank=1 data_name="data" item=$item}}
    {{/foreach}}
    }
}