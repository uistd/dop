<?php

namespace ffan\dop\build;

use ffan\dop\Manager;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Render
 * @package ffan\dop\build
 */
class Render
{
    /**
     * @var string 生成配置名
     */
    private $build_name;

    /**
     * @var string 文件名
     */
    private $file_name;

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
     * Render constructor.
     * @param Manager $manager
     * @param \DOMElement $node
     */
    public function __construct(Manager $manager, \DOMElement $node)
    {
        $this->manager = $manager;
    }

    /**
     * 解析
     * @param \DOMElement $node
     */
    private function parse($node)
    {
        $build_name = PluginRule::read($node, 'build_name');
        if (!empty($build_name)) {
            $this->build_name = $build_name;
        }
        $buf_name = PluginRule::read($node, 'buf_name');
        if (!empty($buf_name)) {
            $this->buf_name = $buf_name;
        }
        $file_name = PluginRule::read($node, 'file');
        if (!empty($file_name)) {
            $this->file_name = $file_name;
        }
        $code_str = trim($node->nodeValue);
        $code_arr = FFanStr::split($code_str, PHP_EOL);
        $this->code = join(PHP_EOL, $code_arr);
    }
}
