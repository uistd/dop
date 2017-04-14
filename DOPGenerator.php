<?php

namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Config as FFanConfig;

/**
 * Class DOPGenerator 生成文件
 * @package ffan\dop
 */
abstract class DOPGenerator
{
    /**
     * @var ProtocolManager
     */
    protected $protocol_manager;

    /**
     * @var int 文件单位
     */
    protected $file_unit;

    /**
     * @var BuildOption 生成参数
     */
    protected $build_opt;

    /**
     * Generator constructor.
     * @param ProtocolManager $protocol_manager
     * @param BuildOption $build_opt
     */
    public function __construct(ProtocolManager $protocol_manager, BuildOption $build_opt)
    {
        $this->protocol_manager = $protocol_manager;
        $this->build_opt = $build_opt;
        /**
         * //变量类型的 字符串 表示
         * Tpl::registerGrep('item_type_name', array('ffan\\dop\\ItemType', 'getTypeName'));
         * //生成缩进值
         * Tpl::registerGrep('indent', array('ffan\\dop\\DOPGenerator', 'indentSpace'));
         * //生成临时变量
         * Tpl::registerGrep('tmp_var_name', array('ffan\\dop\\DOPGenerator', 'tmpVarName'));
         * //插件代码
         * Tpl::registerPlugin('plugin_code', array($this, 'pluginCode'));
         */
    }

    /**
     * 处理缩进和空格
     * @param int $rank
     * @return string
     */
    public static function indentSpace($rank)
    {
        static $cache_space = array();
        if (isset($cache_space[$rank])) {
            return $cache_space[$rank];
        }
        $str = str_repeat(' ', $rank * 4);
        $cache_space[$rank] = $str;
        return $str;
    }

    /**
     * 插件代码入口
     * @param array
     * @return string
     */
    public function pluginCode($args)
    {
        $plugin_list = $this->protocol_manager->getPluginList();
        if (null === $plugin_list) {
            return '';
        }
        $plugin_code = '';
        /**
         * @var string $name
         * @var Plugin $plugin
         */
        foreach ($plugin_list as $name => $plugin) {
            $plugin_code .= $plugin->generateCode($args['struct']);
            $plugin_code .= PHP_EOL;
        }
        return $plugin_code;
    }

    /**
     * 生成临时变量
     * @param string $var
     * @param string $type
     * @return string
     */
    public static function tmpVarName($var, $type)
    {
        return $type . '_' . (string)$var;
    }

    /**
     * 生成文件
     */
    public function generate()
    {
        $all_list = $this->protocol_manager->getAll();
        if (empty($all_list)) {
            return;
        }
        $protocol_list = array();
        //整理一下，按namespace分组
        /** @var Struct $struct */
        foreach ($all_list as $struct) {
            $namespace = $struct->getNamespace();
            if (!isset($protocol_list[$namespace])) {
                $protocol_list[$namespace] = array();
            }
            $protocol_list[$namespace][$struct->getClassName()] = $struct;
        }
        foreach ($protocol_list as $namespace => $class_list) {
            $this->generateFile($namespace, $class_list);
        }
        $this->generateCommon();
    }

    /**
     * 生成一些common文件
     */
    protected function generateCommon()
    {

    }

    /**
     * 获取编译的基础目录
     * @return string
     */
    protected function buildBasePath()
    {
        return FFanUtils::fixWithRuntimePath($this->build_opt->build_path);
    }

    /**
     * 初始化模板配置
     */
    protected function initTpl()
    {
        static $is_init = false;
        if ($is_init) {
            return;
        }
        FFanConfig::add('ffan-tpl', array(
            'tpl_dir' => 'tpl'
        ));
        $is_init = true;
    }

    /**
     * 是否需要生成Encode方法
     * @param int $type
     * @return bool
     */
    protected function isBuildPackMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,服务端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
                //如果是Request 客户端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return $result;
    }


    /**
     * 是否需要生成Decode方法
     * @param int $type
     * @return bool
     */
    protected function isBuildDecodeMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,客户端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            //如果是Request 服务端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return $result;
    }

    /**
     * 生成文件名
     * @param string $build_path
     * @param Struct $struct
     * @return string
     */
    abstract protected function buildFileName($build_path, Struct $struct);

    /**
     * 生成文件
     * @param string $namespace 命令空间
     * @param array [Struct] $class_list
     * @throws DOPException
     */
    abstract protected function generateFile($namespace, $class_list);
}
