<?php
require_once '../vendor/autoload.php';

use ffan\php\git\Git;
use ffan\php\git\GitRepo;
use ffan\php\utils\Utils;
use ffan\php\cache\CacheFactory;
use ffan\dop\build\BuildOption;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

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
    $result_msg = array();
    $conf_arr = get_config($project);
    $git_conf = $conf_arr['protocol']['git'];
    get_git_instance($git_conf, $result_msg);
    $branch_list = get_branch_list($git_conf, !empty($_GET['is_force']));
    view('branch', array(
        'project' => $project,
        'branch_list' => $branch_list,
        'result_msg' => join(PHP_EOL, $result_msg)
    ));
}

/**
 * 获取分支列表
 * @param string $git_conf
 * @param bool $is_force 是否强制从远程获取
 * @return array
 */
function get_branch_list($git_conf, $is_force = false)
{
    $branch_list = null;
    $cache_key = $git_conf . '_branch';
    //尝试从缓存获取分支列表
    if (!$is_force) {
        $cache_obj = CacheFactory::get('file');
        $branch_list = $cache_obj->get($cache_key);
    }
    if (!$branch_list) {
        $git_instance = get_git_instance($git_conf);
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
    $result_msg = array();
    $conf_arr = get_config($project);
    $git_instance = get_git_instance($conf_arr['protocol']['git'], $result_msg);
    branch_check($project, $branch);
    if (!empty($_GET['is_force'])) {
        $git_instance->pull();
    }
    $ini_path = Utils::joinPath($git_instance->getRepoPath(), $conf_arr['protocol']['path']);
    $ini_file = Utils::joinFilePath($ini_path, 'build.ini');
    if (!is_file($ini_file)) {
        show_error('没有找到 ' . $ini_file);
    }
    $build_conf = parse_ini_file($ini_file, true);
    $build_list = array();
    $public_conf = array();
    foreach ($build_conf as $key => $each_build) {
        if ('public' === $key) {
            $public_conf = $each_build;
            continue;
        }
        if (0 !== strpos($key, 'build')) {
            continue;
        }
        $key = str_replace('build', '', $key);
        if (empty($key)) {
            $key = 'main';
        }
        $key = trim($key, ':');
        $build_opt = new BuildOption($key, $each_build, $public_conf);
        $side = [];
        if ($build_opt->hasBuildSide(BuildOption::SIDE_SERVER)) {
            $side[] = 'server';
        }
        if ($build_opt->hasBuildSide(BuildOption::SIDE_CLIENT)) {
            $side[] = 'client';
        }
        $build_list[$key] = array(
            'coder' => $build_opt->getCoderName(),
            'packer' => join(', ', $build_opt->getPacker()),
            'side' => join(', ', $side),
            'note' => $build_opt->getNote(),
            'next_step' => has_push_select_step($conf_arr, $key)
        );
    }
    view('build_list', array(
        'project' => $project,
        'build_list' => $build_list,
        'branch' => $branch,
        'result_msg' => join(PHP_EOL, $result_msg)
    ));
}

/**
 * 代码生成好后的推送分支列表
 */
function action_push_list($project)
{
    $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
    if (empty($branch)) {
        show_error('缺少branch 参数');
    }
    $conf_arr = get_config($project);
    $result_msg = array();
    $build_name = isset($_GET['build']) ? $_GET['build'] : 'main';
    if (!has_push_select_step($conf_arr, $build_name)) {
        show_error('没有推送git 配置');
    }
    $push_set = $conf_arr['push'][$build_name];
    get_git_instance($push_set['git'], $result_msg);
    if (empty($_GET['push_branch'])) {
        $view_set = array(
            'project' => $project,
            'build_name' => $build_name,
            'branch' => $branch,
            'push_branch_list' => get_branch_list($push_set['git'], !empty($_GET['is_force'])),
            'result_msg' => join(PHP_EOL, $result_msg)
        );
        view('push_branch_list', $view_set);
    }
}

/**
 * 分支检查
 * @param string $project
 * @param string $branch
 */
function branch_check($project, $branch)
{
    $conf_arr = get_config($project);
    $git_conf = $conf_arr['protocol']['git'];
    $git_instance = get_git_instance($git_conf);
    $branch_list = get_branch_list($git_conf);
    if (!in_array($branch, $branch_list)) {
        show_error('远程没有找到指定的分支:' . $branch);
    }
    $local_branch_list = $git_instance->getLocalBranch();
    $local_branch = str_replace('origin/', '', $branch);
    //如果当前使用的分支,不是要生成代码的分支
    if ($local_branch_list['use'] !== $local_branch) {
        //如果
        if (!in_array($local_branch, $local_branch_list['branch'])) {
            $git_instance->fetch($local_branch);
        }
    }
}

/**
 * 是否存在 选择 push 代码分支选择
 * @param array $conf_arr
 * @param string $build_name
 * @return bool
 */
function has_push_select_step($conf_arr, $build_name)
{
    if (!isset($conf_arr['push'][$build_name])) {
        return false;
    }
    $conf_arr = $conf_arr['push'][$build_name];
    return isset($conf_arr['git'], $conf_arr['path']);
}

/**
 * 代码生成
 * @param string $project
 */
function action_build($project)
{
    $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
    if (empty($branch)) {
        show_error('缺少branch 参数');
    }
    $conf_arr = get_config($project);
    $result_msg = array();
    $build_name = isset($_GET['build']) ? $_GET['build'] : 'main';
    if (has_push_select_step($conf_arr, $build_name)) {
        $push_set = $conf_arr['push'][$build_name];
        $push_git = get_git_instance($push_set['git'], $result_msg);
        if (empty($_GET['push_branch'])) {
            $view_set = array(
                'project' => $project,
                'build_name' => $build_name,
                'build_branch' => $branch,
                'push_branch' => get_branch_list($push_set['git'], !empty($_GET['is_force']))
            );
            view('push_branch_list', $view_set);
        }
    }
    $protocol_conf = $conf_arr['protocol'];
    $result_msg = array();
    $git_instance = get_git_instance($protocol_conf['git'], $result_msg);
    branch_check($project, $branch);
    $git_instance->pull();
    $status_re = $git_instance->status(true);
    $status_msg = trim($status_re['result']);
    //当前分支不为空,重置分支
    if (!empty($status_msg)) {
        $git_instance->run('reset --hard ' . $branch);
    }
    $base_path = Utils::joinPath($git_instance->getRepoPath(), $conf_arr['protocol']['path']);
    $manager = new ffan\dop\Manager($base_path);
    $result_msg[] = 'build ' . $build_name;
    $re = $manager->build($build_name);
    $build_re = 'build ';
    $build_re .= $re ? 'success' : 'failed';
    $result_msg[] = $build_re;
    $result_msg[] = $manager->getBuildLog();
    if ($re) {
        //如果 配置了push 仓库
        if (isset($conf_arr['push'][$build_name], $conf_arr['push'][$build_name]['git'])) {
            $push_git = get_git_instance($conf_arr['push'][$build_name]['git']);

        } else {
            $git_re = $git_instance->status(true);
            if (!empty($git_re['result'])) {
                $git_instance->add();
                $git_instance->commit('Dop tool generate code');
                $git_instance->push();
            } else {
                $result_msg[] = 'Nothing to commit';
            }
        }
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
    if (!isset($conf['protocol'])) {
        show_error('配置文件中必须包括 protocol 配置');
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
        if (!isset($tmp_conf['protocol'])) {
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
