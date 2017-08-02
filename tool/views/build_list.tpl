{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择代码生成配置</div>
    <div class="panel-body">
        <div class="list-group">
            {{$i = 0}}
            {{foreach $build_list as $name => $build_info}}
                <a class="list-group-item{{if 0 === $i}} active{{/if}}" data-project="{{$project}}" data-build-name="{{$name}}">
                    {{$build_info.title}}
                </a>
                {{$i++}}
            {{/foreach}}
    </div>
    <div class="panel-footer">
        <button type="button" class="btn btn-primary" id="build_button">生成</button>
    </div>
</div>


<script>
    $('.list-group-item').click(function(){
        var target_node = this;
        $('.list-group-item').each(function(i, item){
            var $item = $(item);
            if (item === target_node) {
                $item.addClass('active');
            } else {
                $item.removeClass('active');
            }
        });
    });
    $('#build_button').click(function(){
        var item = $('.list-group-item.active');
        if (0 === item.length) {
            return;
        }
        var project = item.data('project');
        var build_name = item.data('build-name');
        var url = 'index.php?a=build&project=' + project + "&build_name="+ build_name;
        window.location.href = url;
    });
</script>
{{include file="foot.tpl"}}