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
}