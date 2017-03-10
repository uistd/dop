{*变量类型*}
{{$item_type = $item->type|item_type_name}}
{{if !isset($isset_check)}}
    {{$isset_check = false}}
{{/if}}
{*枚举数组*}
{{if 'list' === $item_type}}
    {*值变量*}
    {{$result_var_name = $rank|tmp_var_name:'tmp_arr'}}
    {{$rank|indent}}${{$result_var_name}} = array();
    {{if $isset_check}}
        {{$rank|indent}}if (is_array(${{$var_name}})) {
        {*缩进*}
        {{$rank++}}
    {{else}}
        {{$rank|indent}}if (!is_array(${{$var_name}})) {
        {{$rank|indent}}    continue;
        {{$rank|indent}}}
    {{/if}}
    {*循环变量*}
    {{$for_var_name = $rank|tmp_var_name:'item'}}
    {*list类型*}
    {{$sub_item = $item->item}}
    {{$rank|indent}}foreach (${{$var_name}} as ${{$for_var_name}}) {
    {*缩进*}
    {{$rank++}}
    {{php_export_array var_name=$for_var_name result_var=$result_var_name|joinStr:'[]' rank=$rank item=$sub_item}}
    {*退格*}
    {{$rank--}}
    {{$rank|indent}}}
    {{if $isset_check}}
    {*退格*}
    {{$rank--}}
    {{$rank|indent}}}
    {{/if}}
    {{$rank|indent}}${{$result_var}} = ${{$result_var_name}};
{*关联数组*}
{{elseif 'map' === $item_type}}
    {*值变量*}
    {{$result_var_name = $rank|tmp_var_name:'tmp_map'}}
    {{$rank|indent}}${{$result_var_name}} = array();
    {{if $isset_check}}
        {{$rank|indent}}if (is_array(${{$var_name}})) {
        {*缩进*}
        {{$rank++}}
    {{else}}
        {{$rank|indent}}if (!is_array(${{$var_name}})) {
        {{$rank|indent}}    continue;
        {{$rank|indent}}}
    {{/if}}
    {*循环变量——键*}
    {{$key_var_name = $rank|tmp_var_name:'key'}}
    {*循环变量——值*}
    {{$for_var_name = $rank|tmp_var_name:'item'}}
    {*key类型*}
    {{$key_item = $item->key_item}}
    {*value类型*}
    {{$value_item = $item->value_item}}
    {{$rank|indent}}foreach (${{$var_name}} as ${{$key_var_name}} => ${{$for_var_name}}) {
    {*缩进*}
    {{++$rank}}
    {{php_export_array var_name=$for_var_name result_var=$for_var_name rank=$rank item=$value_item}}
    {{php_export_array var_name=$key_var_name result_var=$key_var_name rank=$rank item=$key_item}}
    {{$rank|indent}}${{$result_var_name}}[${{$key_var_name}}] = ${{$for_var_name}};
    {*退格*}
    {{--$rank}}
    {{$rank|indent}}}
    {{if $isset_check}}
        {*退格*}
        {{$rank--}}
        {{$rank|indent}}}
    {{/if}}
    {{$rank|indent}}${{$result_var}} = ${{$result_var_name}};
{*对象*}
{{elseif 'struct' === $item_type}}
    {{if $isset_check}}
        {{$rank|indent}}if ((${{$var_name}} instanceof {{$item->struct_name}})) {
        {{$rank|indent}}    ${{$result_var}} = ${{$var_name}}->toArray();
        {{$rank|indent}}} else {
        {{$rank|indent}}    ${{$result_var}} = array();
        {{$rank|indent}}}
    {{else}}
        {{$rank|indent}}if (!${{$var_name}} instanceof {{$item->struct_name}}) {
        {{$rank|indent}}    continue;
        {{$rank|indent}}}
        {{$rank|indent}}${{$result_var}} = ${{$var_name}}->toArray();
    {{/if}}
{*其它类型*}
{{else}}
    {*值类型转换*}
    {{$convert = $item->type|php_convert_value}}
    {{if $isset_check}}
        {{$rank|indent}}${{$result_var}} = null === ${{$var_name}} ?: {{$convert}}${{$var_name}};
    {{else}}
        {{$rank|indent}}${{$result_var}} = {{$convert}}${{$var_name}};
    {{/if}}
{{/if}}