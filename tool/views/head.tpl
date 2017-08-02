<!doctype html>
<html lang="en">
<meta content="text/html; charset=utf-8" http-equiv="content-type"/>
<head>
    <meta charset="UTF-8">
    <title>{{if isset($project_name)}}{{$project_name}}{{else}}demo{{/if}}</title>
    <link rel="stylesheet" href="https://nres.ffan.com/xadmin/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://nres.ffan.com/xadmin/css/font-awesome.min.css"/>
    <script src="https://admin.ffan.com/Public/js/jquery-2.1.0.min.js"></script>
</head>
<style>
    #main_body{
        width:90%;
        margin: 20px auto;
    }
    #main_content{
        padding-top:30px !important;
    }
    .hide{
        display: none;
    }
</style>
<body>
<div id="main_body">
    <div class="container">
            {{if empty($config_list)}}
                <div>没有可用的配置</div>
            {{else}}
                <ul class="nav nav-pills">
                    {{foreach $config_list as $name => $each_conf}}
                        <li>
                            <a href="index.php?a=build_list&project={{$name}}">{{$each_conf['title']}}</a>
                        </li>
                    {{/foreach}}
                </ul>
            {{/if}}
        </div>
        <div class="container" id="main_content">