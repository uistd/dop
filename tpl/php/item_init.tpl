{{$rank++}}
{*值是否是数组，如果是数组，需要加 isset *}
{{if isset($key_name)}}
    {{$array_check = true}}
    {{$data_value = $data_name|joinStr:"['"|joinStr:$key_name|joinStr:"']"}} 
{{else}}
    {{$data_value = $data_name}}
    {{$key_name = $var_name}}
    {{$array_check = false}}
{{/if}}
{*生成缩进空白*}
{{$blank_space = str_repeat(' ', ($rank + 1 ) * 4)}}
{{$item_type = $item->type|item_type_name}}
{*判断数组存在这个key 如果是list struct map，还需要判断这个key是否是数组*}
{{if $array_check}}
    {{$blank_space}}if (isset(${{$data_value}}){{if $item->type|php_array_check}} && is_array(${{$data_value}}){{/if}}) {
{{/if}}
{*判断值类型是不是简单的类型，如果是，就可以直接赋值*}
{{if $item->type|php_simple_type}}
    {*值类型转换*}
    {{$convert = $item->type|php_convert_value}}
    {{$blank_space}}    ${{$var_name}} = {{$convert}}${{$data_value}};
{*对象*}
{{elseif 'struct' === $item_type}}
    {{$tmp_var_name = $key_name|tmp_var_name:'struct'}}
    {*这里多写4个空格，因为连续的 模板标签之间的空格会被忽略*}
    {{$blank_space}}    ${{$tmp_var_name}} = new {{$item->struct_name}};
    {{$blank_space}}    ${{$tmp_var_name}}->init(${{$data_value}});
    {{$blank_space}}    ${{$var_name}} = ${{$tmp_var_name}};
{*枚举数组*}
{{elseif 'list' === $item_type}}
    {*循环变量*}
    {{$for_var_name = $rank|tmp_var_name:'item'}}
    {*值变量*}
    {{$result_var_name = $rank|tmp_var_name:'result'}}
    {{$blank_space}}    ${{$result_var_name}} = array();
    {*list类型*}
    {{$sub_item = $item->item}}
    {{$blank_space}}    foreach (${{$data_value}} as ${{$for_var_name}}) {
{{php_item_init var_name=$for_var_name rank=$rank data_name=$for_var_name item=$sub_item}}
    {{$blank_space}}        ${{$result_var_name}}[] = ${{$for_var_name}};
    {{$blank_space}}    }
    {{$blank_space}}    ${{$var_name}} = ${{$result_var_name}};
{*关联数组*}
{{elseif 'map' === $item_type}}
    {*循环变量——键*}
    {{$key_var_name = $rank|tmp_var_name:'key'}}
    {*循环变量——值*}
    {{$for_var_name = $rank|tmp_var_name:'item'}}
    {*值变量*}
    {{$result_var_name = $rank|tmp_var_name:'result'}}
    {{$blank_space}}    ${{$result_var_name}} = array();
    {*key类型*}
    {{$key_item = $item->key_item}}
    {*value类型*}
    {{$value_item = $item->value_item}}
    {{$blank_space}}foreach (${{$data_value}} as ${{$key_var_name}} => ${{$for_var_name}}) {
{{php_item_init var_name=$key_var_name rank=$rank data_name=$key_var_name item=$key_item}}
{{php_item_init var_name=$for_var_name rank=$rank data_name=$for_var_name item=$value_item}}
    {{$blank_space}}    ${{$result_var_name}}[${{$key_var_name}}] = ${{$for_var_name}};
    {{$blank_space}}    }
    {{$blank_space}}    ${{$var_name}} = ${{$result_var_name}};
{{/if}}
{{if $array_check}}
    {{$blank_space}}}
{{/if}}