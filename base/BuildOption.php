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
     * 使用json打包
     */
    const PACK_TYPE_JSON = 0x1;

    /**
     * 使用二进制打包
     */
    const PACK_TYPE_BINARY = 0x2;

    /**
     * 使用msgpack打包
     */
    const PACK_TYPE_MSGPACK = 0x4;

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
     * @var int 数据打包类型，默认是JSON
     */
    public $pack_type = self::PACK_TYPE_JSON;

    /**
     * @var string 使用的插件, plugin1, plugin2
     */
    public $use_plugin = 'all';

    /**
     * @var string 语言类型
     */
    private $code_type;

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
        $this->use_plugin = str_replace(' ', '', $this->use_plugin) . ',';
        $this->code_type = $code_type;
    }

    /**
     * 是否使用某个插件
     * @param string $plugin_name 插件名称
     * @return boolean
     */
    public function usePlugin($plugin_name)
    {
        //如果配置了all，表示使用所有的插件
        if (false !== strpos($this->use_plugin, 'all,')) {
            return true;
        }
        $plugin_name .= ',';
        return false !== strpos($this->use_plugin, $plugin_name);
    }

    /**
     * 获取代码类型
     * @return string
     */
    public function getCodeType()
    {
        return $this->code_type;
    }
}
