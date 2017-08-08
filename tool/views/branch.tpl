{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择sss分支 【<a href="index.php?a=branch&project={{$project}}&is_force=1">刷新分支</a>】</div>
    <div class="panel-body">
        <div class="list-group">
            {{foreach $branch_list as $branch}}
                <a class="list-group-item" data-project="{{$project}}"
                   data-branch="{{$branch}}">
                    {{$branch}}
                </a>
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