{{include file="head.tpl"}}

<div class="panel panel-default">
    <div class="panel-heading">请选择配置
        【<a href="index.php?a=build_list&project={{$project}}&branch={{$branch}}&is_force=1">刷新列表</a>】
    </div>
    <div class="panel-body">
        <div class="list-group">
            {{$i = 0}}
            {{foreach $build_list as $name => $conf}}
                <a class="list-group-item{{if 0 === $i}} active{{/if}}" data-name="{{$name}}">
                    【{{$name}}{{if !empty($conf.note)}} {{$conf.note}}{{/if}}】语言: {{$conf.coder}} 方法:
                    {{if empty($conf.packer)}}
                        none
                    {{else}}
                        {{$conf.packer}}
                        side: {{$conf.side}}
                    {{/if}}
                </a>
                {{$i++}}
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
        var item = $('.list-group-item.active');
        if (0 === item.length) {
            return;
        }
        var project = '{{$project}}';
        var branch = '{{$branch}}';
        var build_name = item.data('name');
        var url = 'index.php?a=build&project=' + project + "&branch=" + branch + '&build=' + build_name;
        window.location.href = url;
    });
</script>
{{include file="foot.tpl"}}