<?php

namespace FFan\Dop\Build;

use FFan\Dop\Exception;
use FFan\Dop\Manager;

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
     * @param \DOMElement $node
     */
    public function __construct(Manager $manager, \DOMElement $node)
    {
        $this->manager = $manager;
        $this->parse($node);
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
     * @param \DOMElement $node
     * @throws Exception
     */
    private function parse($node)
    {
        $shader_name = NodeBase::read($node, 'name');
        if (empty($shader_name)) {
            throw new Exception('Shader name missing');
        }
        $this->shader_name = $shader_name;
        $file_name = NodeBase::read($node, 'file');
        if (!empty($file_name)) {
            $this->file_key = $file_name;
        }
        $path_name = NodeBase::read($node, 'path');
        if (!empty($path_name)) {
            $this->path_key = $path_name;
        }
        $code_node_list = $node->childNodes;
        $num = 0;
        $section_name = $this->manager->getCurrentBuildOpt()->getSectionName();
        for ($i = 0; $i < $code_node_list->length; ++$i) {
            $code_node = $code_node_list->item($i);
            if (XML_ELEMENT_NODE !== $code_node->nodeType) {
                continue;
            }
            $num++;
            if ($code_node->hasAttribute('trigger_buf')) {
                //trigger_buf + section + method 为最终的buf_name
                $buf_name = $code_node->getAttribute('trigger_buf');
                if ($code_node->hasAttribute('section')) {
                    $buf_name .= '_' . $code_node->getAttribute('section');
                } else {
                    $buf_name .= '_' . $section_name;
                }
                if ('unpack' === $code_node->getAttribute('method')) {
                    $buf_name .= '_unpack';
                } else {
                    $buf_name .= '_pack';
                }
            } else {
                $buf_name = $code_node->getAttribute('buf_name');
            }
            $this->addCode($code_node->nodeValue, $buf_name);
        }
        if (0 === $num) {
            $this->addCode($node->nodeValue);
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
