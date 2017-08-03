<?php
require_once '../vendor/autoload.php';

use ffan\php\git\Git;
use ffan\php\git\GitRepo;
use ffan\php\utils\Utils;
use ffan\php\cache\CacheFactory;

$config = require('config/config.php');
ffan\php\utils\Config::init($config);

$a = isset($_GET['a']) ? $_GET['a'] : 'main';
$project = isset($_GET['project']) ? $_GET['project'] : 'default';

$method_name = 'action_' . $a;
if (function_exists($method_name)) {
    call_user_func($method_name, $project);
} else {
    show_error('Unknown action:' . $a);
}

/**
 * 打开项目首页
 */
function action_main()
{
    view('result', ['msg' => '请选择要生成代码的配置']);
}

/**
 * 显示分支列表
 * @param string $project
 */
function action_branch($project)
{
    $branch_list = get_branch_list($project, !empty($_GET['is_force']));
    view('branch', array(
        'project' => $project,
        'branch_list' => $branch_list
    ));
}

/**
 * 获取分支列表
 * @param string $project
 * @param bool $is_force 是否强制从远程获取
 * @return array
 */
function get_branch_list($project, $is_force = false)
{
    $branch_list = null;
    $cache_key = $project .'_branch';
    //尝试从缓存获取分支列表
    if (!$is_force) {
        $cache_obj = CacheFactory::get('file');
        $branch_list  = $cache_obj->get($cache_key);
    }
    if (!$branch_list) {
        $conf_arr = get_config($project);
        $git_instance = get_git_instance($conf_arr['protocol']['git']);
        $branch_re = $git_instance->run('branch -r');
        $branch_list = explode(PHP_EOL, trim($branch_re['result']));
        //移除第一行
        array_shift($branch_list);
        foreach ($branch_list as &$each_str) {
            $each_str = trim($each_str);
        }
        $cache_obj = CacheFactory::get('file');
        $cache_obj->set($cache_key, $branch_list);
    }
    return $branch_list;
}

/**
 * 获取项目配置列表
 * @param string $project
 */
function action_build_list($project)
{
    $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
    if (empty($branch)) {
        show_error('缺少branch 参数');
    }
    $branch_list = get_branch_list($project);
    if (!in_array($branch, $branch_list)) {
        show_error('远程没有找到指定的分支:'. $branch);
    }
    $conf_arr = get_config($project);
    $git_instance = get_git_instance($conf_arr['protocol']['git']);
    $local_branch_list = $git_instance->getLocalBranch();
    $local_branch = str_replace('origin/', '', $branch);
    //如果当前使用的分支,不是要生成代码的分支
    if ($local_branch_list['use'] !== $local_branch) {
        //如果
        if (!in_array($local_branch, $local_branch_list['branch'])) {

        }
    }
    return;
    view('build_list', array(
        'project' => $project,
        'build_list' => $conf_arr['build']
    ));
}

/**
 * 代码生成
 * @param string $project
 */
function action_build($project)
{
    $conf_arr = get_config($project);
    $build_name = isset($_GET['build_name']) ? $_GET['build_name'] : 'main';
    if (!isset($conf_arr['build'][$build_name])) {
        show_error('没有配置:' . $build_name);
    }
    $protocol_conf = $conf_arr['protocol'];
    $result_msg = array();
    get_git_instance($protocol_conf['git'], $result_msg);
    build_protocol($project, $build_name, $result_msg);
    view("result", array('msg' => join(PHP_EOL, $result_msg)));
}

/**
 * 生成协议
 * @param string $project
 * @param string $build_name
 * @param array $result_msg
 */
function build_protocol($project, $build_name, array &$result_msg = [])
{
    $conf_arr = get_config($project);

    $git_instance = get_git_instance($conf_arr['build'][$build_name]['git']);
    $git_instance->status(true);
    $base_path = Utils::joinPath($git_instance->getRepoPath(), $conf_arr['protocol']['path']);
    $manager = new ffan\dop\Manager($base_path);
    $result_msg[] = 'build ' . $build_name;
    $re = $manager->build($build_name);
    $build_re = 'build ';
    $build_re .= $re ? 'success' : 'failed';
    $result_msg[] = $build_re;
    $result_msg[] = $manager->getBuildLog();
    if ($re) {
        $git_re = $git_instance->status(true);
        if (empty($git_re['result'])) {
            return;
        }
        $git_instance->add();
        $git_instance->commit('Dop tool generate code at:' . date('Y-m-d H:i:s', time()));
        $git_instance->push();
    }
}

/**
 * 获取项目配置
 * @param string $project
 * @return array
 */
function get_config($project)
{
    static $conf_cache = array();
    if (isset($conf_cache[$project])) {
        return $conf_cache[$project];
    }
    $config_file = 'config/' . $project . '.php';
    if (!is_file($config_file)) {
        show_error('未找到配置文件：' . $project);
    }
    /** @noinspection PhpIncludeInspection */
    $conf = require($config_file);
    if (!isset($conf['protocol']) || !isset($conf['build'])) {
        show_error('配置文件中必须包括 protocol 和 build 配置');
    }
    if (!isset($conf['protocol']['path'])) {
        $conf['protocol']['path'] = 'protocol';
    }
    if (!isset($conf['protocol']['git'])) {
        show_error('没有找到 protocol => git 配置');
    }
    $conf_cache[$project] = $conf;
    return $conf;
}

/**
 * 获取git的配置
 * @param string $conf_name
 * @param array $result_arr
 * @return GitRepo
 */
function get_git_instance($conf_name, &$result_arr = null)
{
    static $all_git = array();
    if (isset($all_git[$conf_name])) {
        return $all_git[$conf_name];
    }
    $git_protocol = Git::get($conf_name);
    if (is_array($result_arr)) {
        $git_protocol->setResultMsg($result_arr);
    }
    $git_protocol->init();
    $all_git[$conf_name] = $git_protocol;
    return $git_protocol;
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
    $data['config_list'] = get_all_build();
    ffan\php\tpl\Tpl::run($tpl, $data);
    exit(0);
}

/**
 * 获取所有的生成设置
 * @return array
 */
function get_all_build()
{
    $dir = __DIR__ . '/config';
    $dh = opendir($dir);
    $result = array();
    while ($file = readdir($dh)) {
        if ('.' === $file || '..' === $file) {
            continue;
        }
        $full_file = $dir . '/' . $file;
        if (is_dir($full_file)) {
            continue;
        }
        if ('.php' !== substr($full_file, -4)) {
            continue;
        }
        /** @noinspection PhpIncludeInspection */
        $tmp_conf = require($full_file);
        //如果有设置这3项内容的
        if (!isset($tmp_conf['protocol']) || !isset($tmp_conf['build'])) {
            continue;
        }
        if (!isset($tmp_conf['title'])) {
            $tmp_conf['title'] = $tmp_conf['protocol']['git'];
        }
        $result[basename($file, '.php')] = $tmp_conf;
    }
    closedir($dh);
    return $result;
}
