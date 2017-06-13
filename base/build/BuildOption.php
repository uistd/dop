<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\protocol\Struct;
use ffan\php\utils\Str as FFanStr;

/**
 * Class BuildOption 生成文件参数
 * @package ffan\dop\build
 */
class BuildOption
{
    /**
     * 服务端 编译 request 的 unpack 和  response的 pack
     */
    const SIDE_SERVER = 1;

    /**
     * 客户端 编译 request 的 pack 和 response的 unpack
     */
    const SIDE_CLIENT = 2;

    /**
     * @var int 指定编译哪一侧的协议
     */
    private $build_side;

    /**
     * @var int 指定编译什么类型struct的代码
     */
    private $build_protocol;

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
            'namespace' => 'ffan\dop',
            'protocol_type' => 'action',
            'code_side' => 'server'
        );
        //修正缺失的配置项
        foreach ($default_config as $name => $value) {
            if (!isset($section_conf[$name])) {
                $section_conf[$name] = isset($public_conf[$name]) ? $public_conf[$name] : $value;
            }
        }
        //如果没有设置coder 直接报错
        if (!isset($section_conf['coder'])) {
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
        $this->namespace_prefix = $section_conf['namespace'];
        $this->use_plugin = str_replace(' ', '', $this->use_plugin) . ',';
        $this->coder_name = $section_conf['coder'];
        $this->build_side = $this->parseCodeSide($section_conf['code_side']);
        $this->build_protocol = $this->parseBuildStructType($section_conf['protocol_type']);
        $this->parsePacker($section_conf['packer']);
    }

    /**
     * 解析 packer配置
     * @param string $packer_set
     */
    public function parsePacker($packer_set)
    {
        $packer = FFanStr::split($packer_set);
        foreach ($packer as $name) {
            $this->addPacker($name);
        }
    }

    /**
     * 是否编译指定类型的协议类型
     * @param int $type
     * @return bool
     */
    public function hasBuildProtocol($type)
    {
        return ($type & $this->build_protocol) > 0;
    }

    /**
     * 是否生成指定side的代码
     * @param int $side 服务器端 或者 客户端
     * @return bool
     */
    public function hasBuildSide($side)
    {
        return ($side & $this->build_side) > 0;
    }
    
    /**
     * 解析build_struct配置
     * @param string $struct_type
     * @return int
     */
    public function parseBuildStructType($struct_type)
    {
        $result = 0;
        $arr = FFanstr::split($struct_type, ',');
        if (in_array('action', $arr)) {
            $result |= Struct::TYPE_REQUEST;
            $result |= Struct::TYPE_RESPONSE;
        }
        if (in_array('data', $arr)) {
            $result |= Struct::TYPE_DATA;
        }
        //默认值
        if (0 === $result) {
            $result = Struct::TYPE_REQUEST|Struct::TYPE_RESPONSE;
            if (self::SIDE_SERVER === $this->build_side) {
                $result |= Struct::TYPE_DATA;
            }
        }
        return $result;
    }

    /**
     * 解析code_side配置
     * @param string $code_side
     * @return int
     */
    public function parseCodeSide($code_side)
    {
        $result = 0;
        $arr = FFanstr::split($code_side, ',');
        if (in_array('client', $arr)) {
            $result |= self::SIDE_CLIENT;
        }
        if (in_array('server', $arr)) {
            $result |= self::SIDE_SERVER;
        }
        //默认值
        if (0 === $result) {
            $result = self::SIDE_SERVER;
        }
        return $result;
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
