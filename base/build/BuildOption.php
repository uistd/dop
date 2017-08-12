<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\protocol\Struct;
use ffan\php\utils\Utils as FFanUtils;
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
     * 生成文件的一些选项
     */
    const FILE_OPTION_UTF8_BOM = 1;
    
    /**
     * @var string 生成文件目录
     */
    private $build_path;

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
     * @var array 代码生成配置
     */
    private $section_conf;

    /**
     * @var string 说明
     */
    private $note;

    /**
     * @var int 生成文件选项
     */
    private $file_option = 0;

    /**
     * @var bool 是否使用驼峰命名
     */
    public $is_camle_name = true;

    /**
     * @var bool 是否保持xml里的名称
     */
    public $is_keep_item_name = false;

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
            'protocol_type' => 'action',
            'code_side' => 'server',
            'utf8_bom' => false,
            'packer' => '',
            'request_class_suffix' => 'Request',
            'response_class_suffix' => 'Response',
        );
        //将Public config append to section_conf
        foreach ($public_conf as $name => $value) {
            if (!isset($section_conf[$name])) {
                $section_conf[$name] = $value;
            }
        }
        //修正缺失的配置项
        foreach ($default_config as $name => $value) {
            if (!isset($section_conf[$name])) {
                $section_conf[$name] = isset($public_conf[$name]) ? $public_conf[$name] : $value;
            }
        }
        //如果没有设置coder 直接报错
        if (!isset($section_conf['coder'])) {
            $section_conf['coder'] = $section_name;
        }
        //命名空间检查
        $ns = rtrim(trim($section_conf['namespace']), '\\/');
        if (!FFanStr::isValidClassName($ns)) {
            $ns = $default_config['namespace'];
        }
        $section_conf['namespace'] = $ns;
        if ($section_conf['utf8_bom']) {
            $this->file_option |= self::FILE_OPTION_UTF8_BOM;
        }
        $this->section_conf = $section_conf;
        $this->init($section_conf);
    }

    /**
     * 数据修正
     * @param array $section_conf
     */
    public function init($section_conf)
    {
        $build_path = $section_conf['build_path'];
        if (isset($this->section_conf['root_path'])) {
            $build_path = FFanUtils::joinPath($this->section_conf['root_path'], $build_path);
        }
        //代码生成目录
        $this->build_path = FFanUtils::fixWithRuntimePath($build_path);
        $this->namespace_prefix = $section_conf['namespace'];
        $this->use_plugin = str_replace(' ', '', $this->use_plugin) . ',';
        $this->coder_name = $section_conf['coder'];
        $this->build_side = $this->parseCodeSide($section_conf['code_side']);
        $this->build_protocol = $this->parseBuildStructType($section_conf['protocol_type']);
        if (isset($section_conf['is_camle_name']) && 0 === (int)$section_conf['is_camle_name']) {
            $this->is_camle_name = false;
        }
        if (isset($section_conf['']))
        $this->parsePacker($section_conf['packer']);
    }

    /**
     * 获取配置
     * @param string $name
     * @param null $default 默认值
     * @return string|null
     */
    public function getConfig($name, $default = null)
    {
        return isset($this->section_conf[$name]) ? $this->section_conf[$name] : $default;
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
     * 获取build_path
     * @return string
     */
    public function getBuildPath()
    {
        return $this->build_path;
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
            $result = Struct::TYPE_REQUEST | Struct::TYPE_RESPONSE;
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
    private function parseCodeSide($code_side)
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

    /**
     * 获取说明(主要给工具使用)
     * @return string
     */
    public function getNote()
    {
        return is_string($this->note) ? $this->note : '';
    }

    /**
     * 获取section 配置
     * @return array
     */
    public function getSectionConf()
    {
        return $this->section_conf;
    }

    /**
     * 获取生成文件的选项
     * @return int
     */
    public function getFileOption()
    {
        return $this->file_option;
    }
}
