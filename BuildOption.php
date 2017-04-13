<?php

namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;

/**
 * Class BuildOption 生成文件参数
 * @package ffan\dop
 */
class BuildOption
{
    /**
     * 服务端
     */
    const SIDE_SERVER = 1;

    /**
     * 客户端
     */
    const SIDE_CLIENT = 2;

    /**
     * 生成代码 php
     */
    const BUILD_CODE_PHP = 'php';

    /**
     * 生成代码 js
     */
    const BUILD_CODE_JS = 'js';

    /**
     * @var string 生成文件目录
     */
    public $build_path;

    /**
     * @var int 指定编译哪一侧的协议
     */
    public $build_side = self::SIDE_CLIENT;

    /**
     * @var string 命名空间前缀
     */
    public $namespace_prefix = 'ffan\\dop\\';

    /**
     * @var bool 是否使用缓存，默认会缓存编译的结果，避免每次都全量编译
     */
    public $allow_cache = true;

    /**
     * @var bool 是否手动require file
     */
    public $php_require_file = false;

    /**
     * 数据修正
     * @param string $code_type
     */
    public function fix($code_type)
    {
        //初始化代码生成目录
        if (null === $this->build_path) {
            $this->build_path = __DIR__ . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;
        }
        $this->build_path = FFanUtils::joinPath($this->build_path, $code_type);
        //命名空间前缀
        if (!is_string($this->namespace_prefix)) {
            $this->namespace_prefix = '';
        } else {
            //清除两边的空格 和 \ / 符号
            $this->namespace_prefix = trim($this->namespace_prefix, '\\/ ');
        }
    }
}
