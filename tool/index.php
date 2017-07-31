<?php
require_once '../vendor/autoload.php';
$config = require('config/config.php');
ffan\php\utils\Config::init($config);

$a = isset($_GET['a']) ? $_GET['a'] : 'main';
$project = isset($_GET['project']) ? $_GET['project'] : 'default';

$method_name = 'action_'. $a;
if (function_exists($method_name)) {
    call_user_func($method_name, $project);
} else {
    show_error('Unknown action:'. $a);
}

/**
 * 打开项目首页
 * @param string $project 项目
 */
function action_main($project)
{
    $conf_arr = get_config($project);
    $data = array(
        'project_conf' => print_r($conf_arr, true),
        'project' => $project
    );
    view('index', $data);
}

/**
 * 代码生成
 * @param string $project
 */
function action_build($project)
{
    $conf_arr = get_config($project);
    $protocol_conf = $conf_arr['protocol'];
    $git_protocol = ffan\php\git\Git::get('demo');
    $msg = $git_protocol->init();
    view("result", array('msg' => $msg));
}

/**
 * 获取项目配置
 * @param string $project
 * @return array
 */
function get_config($project)
{
     $config_file = 'config/'. $project .'.php';
     if (!is_file($config_file)) {
        show_error('未找到配置文件：'. $project);
     }
    /** @noinspection PhpIncludeInspection */
     return require($config_file);
}

/**
 * 出错页面
 * @param string $msg
 */
function show_error($msg)
{
    view('error', array('err_msg' => $msg));
}

/**
 * 页面显示
 * @param string $tpl
 * @param array $data
 */
function view($tpl, $data = [])
{
    ffan\php\tpl\Tpl::run($tpl, $data);
    exit(0);
}
