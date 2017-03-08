{{++$rank}}
{{$blank_space = str_repeat(' ', $rank * 4)}}
{{$item_type = $item->type|item_type_name}}
{{if 'list' === $item_type}}
{{else}}
{{$blank_space}}if (isset(${{$data_name}}['{{$var_name}}'])) {
{{if 1 === $rank}}
    {{$blank_space}}$this->{{$var_name}} = ${{$data_name}}['{{$var_name}}'];
{{else}}
    {{$blank_space}}{{$var_name}} = ${{$data_name}}['{{$var_name}}'];
{{/if}}
{{$blank_space}}}
{{/if}}