<?php

namespace FFan\Dop\Build;

use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Dop\Schema\Shader as ShaderSchema;

/**
 * Class Shader 着色器
 * @package FFan\Dop\Build
 */
class Shader
{
    /**
     * @var string 名称
     */
    private $shader_name;

    /**
     * @var string 文件名
     */
    private $file_key = '*';

    /**
     * @var string 目录
     */
    private $path_key = '*';

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var array
     */
    private $codes;

    /**
     * Shader constructor.
     * @param Manager $manager
     * @param ShaderSchema $shader_node
     */
    public function __construct(Manager $manager, ShaderSchema $shader_node)
    {
        $this->manager = $manager;
        $this->parse($shader_node);
    }

    /**
     * 代码列表
     * @param string $code
     * @param string $buf_name 代码写入的buf PHP_EOL 表示在文件末尾
     */
    private function addCode($code, $buf_name = PHP_EOL)
    {
        if (!is_string($buf_name) || empty(trim($buf_name))) {
            $buf_name = PHP_EOL;
        }
        $lines = explode(PHP_EOL, $code);
        //多行
        if (count($lines) > 0) {
            $beg_pos = null;
            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }
                //记录第一个非空格的字符起始位置
                if (null === $beg_pos) {
                    $len = strlen($line);
                    $beg_pos = $len - strlen(ltrim($line));
                }
                $this->codes[$buf_name][] = substr($line, $beg_pos);
            }
        } else {
            $this->codes[$buf_name][] = trim($code);
        }
    }

    /**
     * 解析
     * @param ShaderSchema $shader_node
     * @throws Exception
     */
    private function parse($shader_node)
    {
        $shader_name = $shader_node->get('node');
        if (empty($shader_name)) {
            $this->shader_name = $shader_name;
        }
        $file_name = $shader_node->get('file');
        if (!empty($file_name)) {
            $this->file_key = $file_name;
        }
        $path_name = $shader_node->get('path');
        if (!empty($path_name)) {
            $this->path_key = $path_name;
        }
        $num = 0;
        $section_name = $this->manager->getCurrentBuildOpt()->getSectionName();
        $code_list = $shader_node->getCodes();
        foreach ($code_list as $code_node) {
            $num++;
            if (isset($code_node['trigger_buf'])) {
                //trigger_buf + section + method 为最终的buf_name
                $buf_name = $code_node['trigger_buf'];
                if (isset($code_node['section'])) {
                    $buf_name .= '_' . $code_node['section'];
                } else {
                    $buf_name .= '_' . $section_name;
                }
                if (isset($code_node['method']) && 'unpack' === $code_node['method']) {
                    $buf_name .= '_unpack';
                } else {
                    $buf_name .= '_pack';
                }
            } else {
                $buf_name = $code_node['buf_name'];
            }
            $this->addCode($code_node['code_value'], $buf_name);
        }
        if (0 === $num) {
            $this->addCode($shader_node['code_value']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->shader_name;
    }

    /**
     * 对某个目录应用着色器
     * @param Folder $folder
     */
    public function apply(Folder $folder)
    {
        if (empty($this->codes)) {
            return;
        }
        $files = $folder->search($this->path_key, $this->file_key);
        if (empty($files)) {
            return;
        }
        /** @var FileBuf $file_buf */
        foreach ($files as $file_buf) {
            foreach ($this->codes as $buf_name => $code_arr) {
                if (PHP_EOL === $buf_name) {
                    $code_buf = $file_buf;
                } else {
                    $code_buf = $file_buf->getBuf($buf_name);
                }
                if (!$code_buf) {
                    continue;
                }
                foreach ($code_arr as $code) {
                    $code_buf->pushStr($code);
                }

            }
        }
    }
}
