{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择分支 【<a href="index.php?a=branch&project={{$project}}&is_force=1">刷新分支</a>】</div>
    <div class="panel-body">
        <div class="list-group">
            {{$i = 0}}
            {{foreach $branch_list as $branch}}
                <a class="list-group-item{{if 0 === $i}} active{{/if}}" data-project="{{$project}}"
                   data-branch="{{$branch}}">
                    {{$branch}}
                </a>
                {{$i++}}
            {{/foreach}}
        </div>
        <div class="panel-footer">
            <button type="button" class="btn btn-primary" id="next_button">下一步</button>
        </div>
    </div>
</div>
<pre>{{$result_msg}}</pre>
{{include file="common.tpl"}}
<script>
    list_event();
    $('#next_button').click(function () {
        disable_btn($(this));
        var item = $('.list-group-item.active');
        if (0 === item.length || $(this).hasClass('btn-disable')) {
            return;
        }
        var project = item.data('project');
        var branch = item.data('branch');
        var url = 'index.php?a=build_list&project=' + project + "&branch=" + branch;
        window.location.href = url;
    });
</script>
{{include file="foot.tpl"}}