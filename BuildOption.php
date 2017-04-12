<?php

namespace ffan\dop;

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
    public $build_side;

    /**
     * @var string 命名空间前缀
     */
    public $namespace_prefix = 'ffan\\dop\\';

    /**
     * @var bool 是否使用缓存，默认会缓存编译的结果，避免每次都全量编译
     */
    public $allow_cache = true;
    
    /**
     * BuildOption constructor.
     */
    public function __construct()
    {
        //初始化代码生成目录
        if (null === $this->build_path) {
            $this->build_path = __DIR__ . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;
        }
    }
}
