<?php

namespace UiStd\Dop\Build;

use UiStd\Common\Utils;
use UiStd\Dop\Exception;
use UiStd\Dop\Manager;
use UiStd\Common\Utils as UisUtils;

/**
 * Class Folder 虚拟目录
 * @package UiStd\Dop
 */
class Folder
{
    /**
     * @var array 文件列表
     */
    private $file_list = array();

    /**
     * @var string 基础目录
     */
    private $base_dir;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * Folder constructor.
     * @param string $base_dir 基础目录
     * @param Manager $manager
     */
    public function __construct($base_dir, Manager $manager)
    {
        $this->base_dir = UisUtils::fixWithRootPath($base_dir);
        $this->manager = $manager;
    }

    /**
     * 目录名检查
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function checkPathName($path)
    {
        $path = trim($path, " ./\r\n\0\x0B\t");
        if ('' === $path) {
            return '/';
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z_\d]*(\/[a-zA-Z_][a-zA-Z_\d]*)*$/', $path)) {
            throw new Exception('Invalid path name:' . $path);
        }
        return $path;
    }

    /**
     * 文件名检查
     * @param string $file_name
     * @return string
     * @throws Exception
     */
    private function checkFileName($file_name)
    {
        $file_name = trim($file_name);
        if (!preg_match('/^[a-zA-Z\d_]+(\.[a-zA-Z]+)?$/', $file_name)) {
            throw new Exception('Invalid file name:' . $file_name);
        }
        return $file_name;
    }

    /**
     * 创建一个文件
     * @param string $path
     * @param string $file_name
     * @return FileBuf
     * @throws Exception
     */
    public function touch($path, $file_name)
    {
        $path = $this->checkPathName($path);
        $file_name = $this->checkFileName($file_name);
        if ($this->doFind($path, $file_name)) {
            throw new Exception('Path "' . $path . '" file "' . $file_name . '" exist');
        }
        $dop_file = new FileBuf($file_name);
        $this->doAdd($path, $dop_file);
        return $dop_file;
    }

    /**
     * 获取文件夹里某个文件
     * @param string $path
     * @param string $file_name
     * @return FileBuf|null
     */
    public function getFile($path, $file_name)
    {
        $path = $this->checkPathName($path);
        $file_name = $this->checkFileName($file_name);
        return $this->doFind($path, $file_name);
    }

    /**
     * 保存每个文件
     * @param int $option 文件选项
     */
    public function save($option)
    {
        if (empty($this->file_list)) {
            return;
        }
        $this->manager->buildLog('Save file of folder:' . $this->base_dir);
        foreach ($this->file_list as $path => $dir) {
            $abs_path = $this->checkPatch($path);
            $path_file_list = $this->getPathFileMd5($abs_path);
            /**
             * @var string $file_name
             * @var FileBuf $file_buf
             */
            foreach ($dir as $file_name => $file_buf) {
                if ($file_buf->isEmpty()) {
                    return;
                }
                $full_file_name = UisUtils::joinFilePath($abs_path, $file_name);
                $content = $file_buf->dump();
                $log_file_name = $path . '/' . $file_name;
                //如果内容没有发生改变
                if (isset($path_file_list[$file_name])) {
                    $file_md5 = $path_file_list[$file_name];
                    unset($path_file_list[$file_name]);
                    if (md5($content) === $file_md5) {
                        $this->manager->buildLog($log_file_name . ' unchanged.');
                        continue;
                    }
                }
                //utf8 bom头
                if (($option & BuildOption::FILE_OPTION_UTF8_BOM)) {
                    $content = chr(0xEF) . chr(0xBB) . chr(0xBF) . $content;
                }
                $re = file_put_contents($full_file_name, $content);
                $this->manager->buildLog('Build file ' . $log_file_name . ($re ? ' success' : ' failed'));
            }
            //如果有文件未找到
            foreach ($path_file_list as $file_name => $md5) {
                $this->manager->buildLog($path . '/' . $file_name .' removed.');
                unlink($abs_path . $file_name);
            }
        }
    }

    /**
     * 获取目录里所有文件, 以及文件的md5值
     * @param string $path
     * @return array
     */
    private function getPathFileMd5($path)
    {
        $dh = opendir($path);
        $result = array();
        if (!$dh) {
            return $result;
        }
        while ($file = readdir($dh)) {
            if ('.' === $file{0}) {
                continue;
            }
            $full_file = $path . '/' . $file;
            if (is_dir($full_file)) {
                continue;
            }
            $result[$file] = md5_file($full_file);
        }
        return $result;
    }

    /**
     * 直接在指定文件夹下面的某个文件的指定buf里写入代码
     * @param string $path
     * @param string $file_name
     * @param string $buf_name
     * @param string|BufInterface $code
     */
    public function writeToFile($path, $file_name, $buf_name, $code)
    {
        $file = $this->getFile($path, $file_name);
        if (null === $file) {
            return;
        }
        $buf = $file->getBuf($buf_name);
        if (null === $buf) {
            return;
        }
        $buf->push($code);
    }


    /**
     * 目录检查
     * @param string $path
     * @return string
     */
    private function checkPatch($path)
    {
        $file_path = UisUtils::joinPath($this->base_dir, $path);
        UisUtils::pathWriteCheck($file_path);
        return $file_path;
    }

    /**
     * 查找文件
     * @param string $path
     * @param string $file_name
     * @return FileBuf|null
     */
    private function doFind($path, $file_name)
    {
        if (isset($this->file_list[$path][$file_name])) {
            return $this->file_list[$path][$file_name];
        } else {
            return null;
        }
    }

    /**
     * 增加一个文件
     * @param string $path
     * @param FileBuf $dop_file
     */
    private function doAdd($path, $dop_file)
    {
        if (!isset($this->file_list[$path])) {
            $this->file_list[$path] = array();
        }
        $dop_file->setPath($path);
        $this->file_list[$path][$dop_file->getFileName()] = $dop_file;
    }

    /**
     * 搜索所有文件
     * @param string $path_key 文件夹关键字
     * @param string $file_key
     * @return FileBuf[]
     */
    public function search($path_key, $file_key)
    {
        $result = array();
        foreach ($this->file_list as $path => $dir) {
            if (!$this->isKeyMatch($path_key, $path)) {
                continue;
            }
            /**
             * @var string $file_name
             * @var FileBuf $file_buf
             */
            foreach ($dir as $file_name => $file_buf) {
                if ($file_buf->isEmpty()) {
                    continue;
                }
                if (!$this->isKeyMatch($file_key, $file_name)) {
                    continue;
                }
                $result[] = $file_buf;
            }
        }
        return $result;
    }

    /**
     * 判断字符串是否匹配查找关键字
     * @param string $key
     * @param string $str
     * @return bool
     */
    private function isKeyMatch($key, $str)
    {
        if ('*' === $key) {
            return true;
        }
        if ($key === $str) {
            return true;
        }
        $key = str_replace('*', '[a-zA-Z\d_]*', $key);
        return preg_match('#' . $key . '#', $str) > 0;
    }
}
