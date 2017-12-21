<?php

namespace UiStd\Dop\Tool;

use UiStd\Common\Utils;

/**
 * Class PublicSchema 公共协议
 */
class PublicSchema
{
    const TYPE_PULL = 'pull';

    const TYPE_PUSH = 'push';

    /**
     * @var string 当前文件
     */
    private $file;

    /**
     * @var array config
     */
    private $conf;

    /**
     * @var GitHelper[]
     */
    private static $git_instance;

    /**
     * @var string 操作类型 push 或者 pull
     */
    private $type;

    /**
     * PublicSchema constructor.
     * @param string $file
     * @param array $conf_arr
     * @param string $type
     */
    public function __construct($file, $conf_arr, $type)
    {
        $this->file = $file;
        if (!isset($conf_arr['remote'])) {
            echo '[ERROR]未找到 remote 设置', PHP_EOL;
            return;
        }
        if (!isset($conf_arr['file'])) {
            echo '[ERROR]未找到 file 设置', PHP_EOL;
            return;
        }
        $this->conf = $conf_arr;
        $this->type = $type;
        if (self::TYPE_PUSH === $this->type) {
            $this->commitFile();
        } else {
            $this->copyFile();
        }
    }

    /**
     * 从远程仓库复制文件
     */
    private function copyFile()
    {
        $git_helper = $this->getGitInstance($this->conf['remote']);
        $git_helper->pull();
        $repo_file = $this->getRepoFileName();
        if (!is_file($repo_file)) {
            echo '远程仓库' . $this->conf['remote'] . '中不存在文件:' . $this->conf['file'], PHP_EOL;
            return;
        }
        if (!$this->isFileChange($repo_file)) {
            return;
        }
        if (!$this->confirm($repo_file)) {
            return;
        }
        echo "pull!", PHP_EOL;
        copy($repo_file, $this->file);
    }

    /**
     * 提交文件
     */
    private function commitFile()
    {
        $repo_file = $this->getRepoFileName();
        if (is_file($repo_file)) {
            if (!$this->isFileChange($repo_file)) {
                return;
            }
            if (!$this->confirm($repo_file)) {
                return;
            }
        }
        $path = dirname($repo_file);
        Utils::pathWriteCheck($path);
        echo "add file!", PHP_EOL;
        copy($this->file, $repo_file);
        $git_helper = $this->getGitInstance($this->conf['remote']);
        $git = $git_helper->getGitRepo();
        $git->add($repo_file);
    }

    /**
     * 文件是否有变更
     * @param string $repo_file
     * @return bool
     */
    private function isFileChange($repo_file)
    {
        if (md5_file($repo_file) === md5_file($this->file)) {
            echo $this->conf['remote'] . ' file:' . $this->conf['file'] . ' unchanged', PHP_EOL;
            return false;
        }
        return true;
    }

    /**
     * 二次确认
     * @param string $repo_file
     * @return bool
     */
    private function confirm($repo_file)
    {
        echo '远程仓库文件和本地文件不相同', PHP_EOL;
        $cmd = 'diff ' . $repo_file . ' ' . $this->file . ' 2>&1';
        exec($cmd, $result);
        echo PHP_EOL, PHP_EOL, join(PHP_EOL, $result), PHP_EOL, PHP_EOL;
        if (self::TYPE_PUSH === $this->type) {
            echo '是否确认使用本地文件替换远程仓库文件?';
        } else {
            echo '是否确认使用远程仓库文件替换本地文件?';
        }
        echo PHP_EOL, PHP_EOL, "\t确认(y) \t取消(n).", PHP_EOL, PHP_EOL;
        $fp = fopen('php://stdin', 'r');
        $input = fgets($fp, 255);
        fclose($fp);
        $input = strtoupper(trim($input));
        if ('Y' === $input) {
            return true;
        }
        return false;
    }

    /**
     * 获取在仓库中的文件名
     * @string
     */
    private function getRepoFileName()
    {
        $remote = $this->conf['remote'];
        $git_helper = $this->getGitInstance($remote);
        $path = $git_helper->getRepoPath();
        $file_in_git = Utils::joinFilePath($path, $this->conf['file']);
        return $file_in_git;
    }

    /**
     * 获取git
     * @param string $remote
     * @return GitHelper
     */
    private function getGitInstance($remote)
    {
        $key = md5($remote);
        if (isset(self::$git_instance[$key])) {
            return self::$git_instance[$key];
        }
        $conf = array(
            'url' => $remote,
            'username' => 'devtool',
            'email' => '18844626@qq.com',
            'repo_path' => 'git/' . $key
        );
        $build_conf = array('git:' . $key => $conf);
        $git_helper = new GitHelper();
        $git_helper->init($build_conf, $key);
        self::$git_instance[$key] = $git_helper;
        return $git_helper;
    }

    /**
     * 检测整个目录
     * @param string $folder
     * @param string $type
     */
    private static function folderDetect($folder, $type)
    {
        $dir_fd = opendir($folder);
        if (!$dir_fd) {
            return;
        }
        while ($file = readdir($dir_fd)) {
            $file = strtolower($file);
            if ('.' === $file{0}) {
                continue;
            }
            $full_file = Utils::joinFilePath($folder, $file);
            if (is_dir($full_file)) {
                self::folderDetect($full_file, $type);
            }
            if ('.xml' === substr($file, -4)) {
                self::xmlInstance($full_file, $type);
            }
        }
    }

    /**
     * @param \DOMElement $node
     * @return null|array
     */
    private static function getAllAttribute($node)
    {
        $attributes = $node->attributes;
        $count = $attributes->length;
        $result = null;
        for ($i = 0; $i < $count; ++$i) {
            $tmp = $attributes->item($i);
            $name = $tmp->nodeName;
            $value = $tmp->nodeValue;
            $result[$name] = $value;
        }
        return $result;
    }

    /**
     * 获取实例
     * @param string $file_name
     * @param string $type
     */
    private static function xmlInstance($file_name, $type)
    {
        $xml_doc = new \DOMDocument();
        $xml_doc->load($file_name);
        $xml_path = new \DOMXPath($xml_doc);
        $protocol = $xml_path->query('/protocol');
        $main_node = $protocol->item(0);
        if (!$main_node->hasAttribute('public')) {
            return;
        }
        $conf_arr = self::getAllAttribute($main_node);
        echo $file_name . ' is public!', PHP_EOL;
        new self($file_name, $conf_arr, $type);
    }

    /**
     * 写本地的改变写入远程仓库
     * @param string $path
     */
    public static function push($path)
    {
        self::folderDetect($path, self::TYPE_PUSH);
        foreach (self::$git_instance as $git_helper) {
            $git = $git_helper->getGitRepo();
            $git->run('stash');
            $git->pull();
            $git->run('stash pop');
            $git_helper->pushCode();
            $git->run('stash clear');
        }
    }

    /**
     * 用远程的文件更新本地
     * @param string $path
     */
    public static function pull($path)
    {
        self::folderDetect($path, self::TYPE_PULL);
    }
}
