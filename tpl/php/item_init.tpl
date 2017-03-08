{*rank 表示缩进等级*}
{{++$rank}}
{{$blank_space = str_repeat(' ', $rank * 4)}}
{{$item_type = $item->type|item_type_name}}
{{$blank_space}}if (isset(${{$data_name}}['{{$var_name}}']){{if $item->type|php_array_check}} && is_array(${{$data_name}}['{{$var_name}}']){{/if}}) {
{{if $item->type|php_simple_type:$item}}
    {{$blank_space}}    ${{if 2 === $rank}}this->{{/if}}
    {{$var_name}} = ${{$data_name}}['{{$var_name}}'];
{{elseif 'struct' === $item_type}}
    {{$tmp_var_name = $var_name|tmp_var_name:'struct'}}
    {{$blank_space}}${{$tmp_var_name}} = new {{$item->struct_name}};
    {{$blank_space}}${{$tmp_var_name}}->init(${{$data_name}}['{{$var_name}}']);
    {{$blank_space}}${{if 2 === $rank}}this->{{/if}}
    {{$var_name}} = ${{$tmp_var_name}};
{{/if}}
{{$rank|indent_space}}}