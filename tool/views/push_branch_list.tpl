{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择编译的代码推送分支
        【<a href="index.php?a=push_list&project={{$project}}&branch={{$branch}}&build={{$build_name}}&is_force=1">刷新分支</a>】
    </div>
    <div class="panel-body">
        <div class="list-group">
            {{foreach $push_branch_list as $push_branch}}
            <a class="list-group-item" data-push-branch="{{$push_branch}}">
                {{$push_branch}}
            </a>
            {{/foreach}}
        </div>
        <div class="panel-footer">
            <button type="button" class="btn btn-primary" id="build_btn">开始生成</button>
        </div>
    </div>
</div>
<pre>{{$result_msg}}</pre>
{{include file="common.tpl"}}
<script>
    list_event();
    $('#build_btn').click(function () {
        disable_btn($(this));
        var item = $('.list-group-item.active');
        if (0 === item.length || $(this).hasClass('btn-disable')) {
            return;
        }
        var project = '{{$project}}';
        var branch = '{{$branch}}';
        var build_name = '{{$build_name}}';
        var push_branch = item.data('push-branch');
        var url = 'index.php?a=build&project=' + project + "&branch=" + branch + '&build=' + build_name + '&push_branch=' + push_branch;
        window.location.href = url;
    });
</script>
{{include file="foot.tpl"}}