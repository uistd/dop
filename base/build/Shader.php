<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Shader 着色器
 * @package ffan\dop\build
 */
class Shader
{
    /**
     * @var string 生成配置名
     */
    private $build_name;

    /**
     * @var string 文件名
     */
    private $file_name = '*';

    /**
     * @var string 目录
     */
    private $path_name = '*';

    /**
     * @var string buf
     */
    private $buf_name;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var string
     */
    private $code;

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
     * 解析
     * @param \DOMElement $node
     * @throws Exception
     */
    private function parse($node)
    {
        $build_name = PluginRule::read($node, 'build_name');
        if (empty($build_name)) {
            throw new Exception('Shader build_name missing');
        }
        $this->build_name = $build_name;
        $buf_name = PluginRule::read($node, 'buf_name');
        if (!empty($buf_name)) {
            $this->buf_name = $buf_name;
        }
        $file_name = PluginRule::read($node, 'file');
        if (!empty($file_name)) {
            $this->file_name = $file_name;
        }
        $path_name = PluginRule::read($node, 'path');
        if (!empty($path_name)) {
            $this->path_name = $path_name;
        }
        $code_str = trim($node->nodeValue);
        $code_arr = FFanStr::split($code_str, PHP_EOL);
        $this->code = join(PHP_EOL, $code_arr);
    }

    /**
     * @return string
     */
    public function getBuildName()
    {
        return $this->build_name;
    }

    /**
     * 对某个目录应用着色器
     * @param Folder $folder
     */
    public function apply(Folder $folder)
    {

    }

}
