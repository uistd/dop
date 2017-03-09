{{$code_php_tag}}
{{$main_ns = $struct.namespace|php_ns}}
{{$code_namespace}} {{$main_ns}};
{{if $struct.is_extend}}

use {{$struct.parent.namespace|php_ns}}\{{$struct.parent.class}};
{{/if}}
/**
 * Class {{$class_name}} {{if !empty($node)}}$note{{/if}} 
 * @package {{$main_ns}}
 */
class {{$class_name}}{{if $struct.is_extend}} extends {{$struct.parent.class}}{{/if}}
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
{{if $struct.is_extend}}
    parent::init($data);
{{/if}}
{{foreach $struct.item_list as $name => $item}}
    {*循环初始化变量*}
    {{php_item_init var_name="this->"|joinStr:$name key_name=$name rank=0 data_name="data" item=$item}}
{{/foreach}}
    }
}