{{$code_php_tag}}
{{$main_ns = $struct.namespace|php_ns}}
{{$code_namespace}} {{$main_ns}};

{{if !empty($struct.import_struct)}}
    {{foreach $struct.import_struct as $include_name => $v}}
require_once '{{$include_name|php_require:$struct.namespace}}';
    {{/foreach}}
{{/if}}
{{if $struct.is_extend}}
require_once '{{$struct.parent.full_name|php_require:$struct.namespace}}';
{{/if}}
{{if $struct.is_extend && $struct.parent.namespace !== $struct.namespace}}

use {{$struct.parent.namespace|php_ns}}\{{$struct.parent.class}};
{{/if}}

/**
 * Class {{$class_name}} {{if !empty($struct.note)}}{{$struct.note}}{{/if}} 
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