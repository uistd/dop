<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Str as FFanStr;

/**
 * Class BuildOption 生成文件参数
 * @package ffan\dop\build
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
    public $namespace_prefix;

    /**
     * @var array 数据打包解包类
     */
    private $packer_arr = array();

    /**
     * @var string 使用的插件, plugin1, plugin2
     */
    private $use_plugin = 'all';

    /**
     * @var string 语言类型
     */
    private $coder_name;

    /**
     * @var string
     */
    public $section_name;

    /**
     * @var string 二进制数据通用签名key
     */
    public $sign_key;

    /**
     * BuildOption constructor.
     * @param string $section_name
     * @param array $section_conf
     * @param array $public_conf 公共配置
     * @throws Exception
     * @internal param Manager $manager
     */
    public function __construct($section_name, array $section_conf, $public_conf = array())
    {
        $this->section_name = $section_name;
        //默认配置
        static $default_config = array(
            'build_path' => 'build',
            'namespace' => 'ffan\dop',
            'packer' => 'json',
            'sign_key' => 'www.ffan.com'
        );
        //修正缺失的配置项
        foreach ($default_config as $name => $value) {
            if (!isset($section_conf[$name])) {
                $section_conf[$name] = isset($public_conf[$name]) ? $public_conf[$name] : $value;
            }
        }
        //如果没有设置coder 直接报错
        if (!$section_conf['coder']) {
            throw new Exception('`Coder` not found in build config:' . $section_name);
        }
        //命名空间检查
        $ns = rtrim(trim($section_conf['namespace']), '\\/');
        if (!FFanStr::isValidClassName($ns)) {
            $ns = $default_config['namespace'];
        }
        $section_conf['namespace'] = $ns;
        $this->init($section_conf);
    }

    /**
     * 数据修正
     * @param array $section_conf
     */
    public function init($section_conf)
    {
        //代码生成目录
        $this->build_path = FFanUtils::fixWithRootPath($section_conf['build_path']);
        $this->namespace_prefix = $section_conf['namespace'];
        $this->use_plugin = str_replace(' ', '', $this->use_plugin) . ',';
        $this->coder_name = $section_conf['coder'];
        $this->sign_key = $section_conf['sign_key'];
        $packer = FFanStr::split($section_conf['packer'], ',');
        foreach ($packer as $name) {
            $this->addPacker($name);
        }
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
     * 添加一种加密解密方法
     * @param string $packer_name
     * @throws Exception
     */
    public function addPacker($packer_name)
    {
        if (!FFanStr::isValidVarName($packer_name)) {
            throw new Exception('Packer name:' . $packer_name . ' is invalid');
        }
        $this->packer_arr[$packer_name] = true;
    }

    /**
     * 获取pack
     */
    public function getPacker()
    {
        return array_keys($this->packer_arr);
    }

    /**
     * 获取代码生成器名称
     * @return string
     */
    public function getCoderName()
    {
        return $this->coder_name;
    }
}
