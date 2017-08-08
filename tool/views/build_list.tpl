{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择配置
        【<a href="index.php?a=build_list&project={{$project}}&branch={{$branch}}&is_force=1">刷新列表</a>】
    </div>
    <div class="panel-body">
        <div class="list-group">
            {{foreach $build_list as $name => $conf}}
                <a class="list-group-item" data-name="{{$name}}" data-next-step="{{$conf.next_step}}">
                    【{{$name}}{{if !empty($conf.note)}} {{$conf.note}}{{/if}}】语言: {{$conf.coder}} 方法:
                    {{if empty($conf.packer)}}
                        none
                    {{else}}
                        {{$conf.packer}}
                        side: {{$conf.side}}
                    {{/if}}
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
    list_event(function (item) {
        var str;
        if (item.data('next-step')) {
            str = '下一步';
        } else {
            str = '开始生成';
        }
        $('#build_btn').html(str);
    });
    $('#build_btn').click(function () {
        var item = $('.list-group-item.active');
        if (0 === item.length || $(this).hasClass('btn-disable')) {
            return;
        }
        var project = '{{$project}}';
        var branch = '{{$branch}}';
        var build_name = item.data('name'), action_name;
        if (item.data('next-step')) {
            action_name = 'push_list';
        } else {
            action_name = 'build';
        }
        var url = 'index.php?a=' + action_name + '&project=' + project + "&branch=" + branch + '&build=' + build_name;
        window.location.href = url;
    });
</script>
{{include file="foot.tpl"}}