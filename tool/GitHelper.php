<?php
use UiStd\Common\Config;
use UiStd\Git\Git;
use UiStd\Git\GitRepo;
use UiStd\Logger\LoggerBase;

/**
 * Class GitHelper
 */
class GitHelper extends LoggerBase
{
    /**
     * @var GitRepo
     */
    private $git_instance;

    /**
     * @var string
     */
    private $build_name;

    /**
     * 收到日志
     * @param int $log_level
     * @param string $content
     */
    public function onLog($log_level, $content)
    {
        echo $content, PHP_EOL;
    }

    /**
     * 初始化
     * @param array $build_conf
     * @param string $build_name
     */
    public function init($build_conf, $build_name)
    {
        $git_conf = 'git:' . $build_name;
        $git_instance = null;
        if (!isset($build_conf[$git_conf])) {
            return;
        }
        $this->build_name = $build_name;
        $build_conf[$git_conf]['repo_path'] = 'tool';
        Config::add('uis-git:' . $build_name, $build_conf[$git_conf]);
        $this->git_instance = $this->getGitInstance();
        $branch = isset($build_conf[$git_conf]['branch']) ? $build_conf[$git_conf]['branch'] : 'origin/master';
        $this->branchCheck($branch);
    }

    /**
     * 分支检查
     * @param string $branch
     */
    private function branchCheck($branch)
    {
        $branch_list = $this->getBranchList();
        if (!in_array($branch, $branch_list)) {
            exit('远程没有找到指定的分支:' . $branch . "\n");
        }
        $local_branch_list = $this->git_instance->getLocalBranch();
        $local_branch = str_replace('origin/', '', $branch);
        //如果当前使用的分支,不是要生成代码的分支
        if ($local_branch_list['use'] !== $local_branch) {
            //如果
            if (!in_array($local_branch, $local_branch_list['branch'])) {
                $this->git_instance->fetch($local_branch);
            }
        }
        $this->git_instance->pull();
        //当前分支不为空,重置分支
        if (!empty($this->git_instance->getChangeFiles())) {
            $this->git_instance->run('reset --hard ' . $branch);
        }
    }

    /**
     * 获取分支列表
     * @return array
     */
    private function getBranchList()
    {
        $branch_re = $this->git_instance->run('branch -r');
        $branch_list = explode(PHP_EOL, trim($branch_re['result']));
        if (!is_array($branch_list)) {
            return array();
        }
        //移除第一行
        array_shift($branch_list);
        foreach ($branch_list as &$each_str) {
            $each_str = trim($each_str);
        }
        return $branch_list;
    }

    /**
     * 获取git实例
     * @return GitRepo
     */
    private function getGitInstance()
    {
        $git_protocol = Git::get($this->build_name);
        if (!$git_protocol->init()) {
            exit('无法clone git ' . $this->build_name . " 请检查相关配置\n");
        }
        return $git_protocol;
    }

    /**
     * 提交生成的代码
     */
    public function pushCode()
    {
        if (!$this->git_instance) {
            return '';
        }
        $files = $this->git_instance->getChangeFiles();
        if (empty($files)) {
            return 'Nothing to commit';
        }
        foreach ($files as $each_file) {
            $this->git_instance->add($each_file);
        }
        $this->git_instance->commit('Dop tool generate code at '. date('Y-m-d H:i:s', time()));
        $this->git_instance->push();
        return 'finish';
    }
}
