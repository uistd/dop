<?php

namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Config as FFanConfig;

/**
 * Class DOPGenerator 生成文件
 * @package ffan\dop
 */
class DOPGenerator
{
    /**
     * 插件代码结果类型
     */
    const PLUGIN_CODE_BEGIN = 0;
    const PLUGIN_CODE_FINISH = 1;
    const PLUGIN_CODE_BY_CLASS = 2;
    const PLUGIN_CODE_BY_XML = 3;
    /**
     * @var ProtocolManager
     */
    protected $manager;

    /**
     * @var int 文件单位
     */
    protected $file_unit;

    /**
     * @var BuildOption 生成参数
     */
    protected $build_opt;

    /**
     * @var string 生成代码基础路径
     */
    protected $build_base_path;

    /**
     * @var array 插件代码结果缓存
     */
    private $plugin_code_result;

    /**
     * @var array 插件的代码生成器
     */
    private $plugin_generator;

    /**
     * Generator constructor.
     * @param ProtocolManager $protocol_manager
     * @param BuildOption $build_opt
     */
    public function __construct(ProtocolManager $protocol_manager, BuildOption $build_opt)
    {
        $this->manager = $protocol_manager;
        $this->build_opt = $build_opt;
        $this->build_base_path = FFanUtils::fixWithRuntimePath($this->build_opt->build_path);
        $this->initTpl();
    }

    /**
     * 获取代码生成参数
     * @return BuildOption
     */
    public function getBuildOption()
    {
        return $this->build_opt;
    }

    /**
     * 获取代码生成的基础路径
     * @return string
     */
    public function getBuildBasePath()
    {
        return $this->build_base_path;
    }

    /**
     * 获取协议管理器
     * @return ProtocolManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * 获取插件代码生成器
     * @return array
     */
    public function getPluginCoder()
    {
        if (NULL !== $this->plugin_generator) {
            return $this->plugin_generator;
        }
        $result = array();
        $code_type = $this->build_opt->getCodeType();
        $plugin_list = $this->manager->getPluginList();
        if (null !== $plugin_list) {
            /**
             * @var string $name
             * @var Plugin $plugin
             */
            foreach ($plugin_list as $name => $plugin) {
                if (!$this->build_opt->usePlugin($name)) {
                    continue;
                }
                $coder = $plugin->getPluginCoder($code_type);
                if (null !== $coder) {
                    $result[$name] = $coder;
                }
            }
        }
        $this->plugin_generator = $result;
        return $result;
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
        //插件代码生成的结果 保存在临时变量里
        $this->plugin_code_result = array(
            self::PLUGIN_CODE_BEGIN => [],
            self::PLUGIN_CODE_FINISH => [],
            self::PLUGIN_CODE_BY_CLASS => [],
            self::PLUGIN_CODE_BY_XML => []
        );
        $this->pluginCodeCommon();
        $coder = $this->getCoder();
        $coder->codeCommon();
        $use_cache = $this->build_opt->allow_cache;
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            if ($use_cache && $struct->isCached()) {
                continue;
            }
            $coder->codeByClass($struct);
        }
        $file_list = $use_cache ? $this->manager->getBuildFileList() : $this->manager->getAllFileList();
        foreach ($file_list as $file) {
            $this->pluginCodeByXml($file);
            $coder->codeByXml($file);
        }
    }

    /**
     * 生成插件代码 开始
     */
    private function pluginCodeCommon()
    {
        $plugin_generator = $this->getPluginCoder();
        /**
         * @var string $name
         * @var PluginCoder $coder
         */
        foreach ($plugin_generator as $name => $coder) {
            $coder->codeCommon();
        }
    }

    /**
     * 生成插件代码 => 按xml文件名
     * @param string $file_name
     */
    private function pluginCodeByXml($file_name)
    {
        $plugin_generator = $this->getPluginCoder();
        /**
         * @var string $name
         * @var PluginCoder $coder
         */
        foreach ($plugin_generator as $name => $coder) {
            $coder->codeAsFile($file_name);
        }
    }

    /**
     * 获取代码生成对象
     * @return CoderBase
     * @throws DOPException
     */
    private function getCoder()
    {
        $code_type = $this->build_opt->getCodeType();
        $class_name = 'Coder';
        $file = dirname(__DIR__) . '/pack/' . $code_type . '/' . $class_name . '.php';
        if (!is_file($file)) {
            throw new DOPException('Can not find coder file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = '\ffan\dop\pack\\' . $code_type . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new DOPException('Unknown class name ' . $full_class);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['ffan\dop\CoderBase'])) {
            throw new DOPException('Class ' . $full_class . ' must be implements of CoderBase');
        }
        return new $full_class($this, $code_type);
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
            'tpl_dir' => 'pack/tpl'
        ));
        $is_init = true;
    }

    /**
     * 生成文件
     * @param string $file_name
     * @param string $contents
     * @throws DOPException
     */
    public function makeFile($file_name, $contents)
    {
        static $path_check = array();
        $full_file_name = FFanUtils::joinFilePath($this->build_base_path, $file_name);
        $dir = dirname($full_file_name);
        if (!isset($path_check[$dir])) {
            FFanUtils::pathWriteCheck($dir);
            $path_check[$dir] = true;
        }
        $re = file_put_contents($full_file_name, $contents);
        if (false === $re) {
            throw new DOPException('Can not write file ' . $full_file_name);
        }
        $this->manager->buildLog('Build file ' . $file_name . ' success');
    }
}
