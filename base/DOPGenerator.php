<?php

namespace ffan\dop;

use ffan\dop\pack\php\Generator;
use ffan\dop\plugin\Plugin;
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
    private function getPluginGenerator()
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
                $generator = $plugin->getGenerator($code_type);
                if (null !== $generator) {
                    $result[$name] = $generator;
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
        $this->pluginCodeBegin();
        $generator = $this->getGenerator();
        $generator->generateBegin($this);
        $use_cache = $this->build_opt->allow_cache;
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            if ($use_cache && $struct->isCached()) {
                continue;
            }
            $this->pluginCodeByClass($struct);
            $generator->generateByClass($this, $struct);
        }
        $file_list = $use_cache ? $this->manager->getBuildFileList() : $this->manager->getAllFileList();
        foreach ($file_list as $file) {
            $this->pluginCodeByXml($file);
            $generator->generateByXml($this, $file);
        }
        $this->pluginCodeFinish();
        $generator->generateFinish($this);
    }

    /**
     * 生成插件代码 开始
     */
    private function pluginCodeBegin()
    {
        $plugin_generator = $this->getPluginGenerator();
        /**
         * @var string $name
         * @var GenerateInterface $generator
         */
        foreach ($plugin_generator as $name => $generator) {
            $this->plugin_code_result[self::PLUGIN_CODE_BEGIN][$name] = $generator->generateBegin($this);
        }
    }

    /**
     * 生成插件代码 结束
     */
    private function pluginCodeFinish()
    {
        $plugin_generator = $this->getPluginGenerator();
        /**
         * @var string $name
         * @var GenerateInterface $generator
         */
        foreach ($plugin_generator as $name => $generator) {
            $this->plugin_code_result[self::PLUGIN_CODE_FINISH][$name] = $generator->generateFinish($this);
        }
    }

    /**
     * 生成插件代码 => 按类名
     * @param Struct $struct
     */
    private function pluginCodeByClass($struct)
    {
        $plugin_generator = $this->getPluginGenerator();
        if (empty($plugin_generator)) {
            return;
        }
        $struct_name = $struct->getClassName();
        /**
         * @var string $name
         * @var GenerateInterface $generator
         */
        foreach ($plugin_generator as $name => $generator) {
            $this->plugin_code_result[self::PLUGIN_CODE_FINISH][$struct_name][$name] = $generator->generateByClass($this, $struct);
        }
    }

    /**
     * 生成插件代码 => 按xml文件名
     * @param string $file_name
     */
    private function pluginCodeByXml($file_name)
    {
        $plugin_generator = $this->getPluginGenerator();
        /**
         * @var string $name
         * @var GenerateInterface $generator
         */
        foreach ($plugin_generator as $name => $generator) {
            $this->plugin_code_result[self::PLUGIN_CODE_FINISH][$file_name][$name] = $generator->generateByXml($this, $file_name);
        }
    }

    /**
     * 获取插件代码 - 开始阶段
     * @param string $plugin_name
     * @return string
     */
    public function getBeginPluginCode($plugin_name)
    {
        $code_arr = $this->plugin_code_result[self::PLUGIN_CODE_BEGIN];
        return isset($code_arr[$plugin_name]) ? $code_arr[$plugin_name] : '';
    }

    /**
     * 获取一个类的插件代码
     * @param string $class_name
     * @param string $plugin_name
     * @return string
     */
    public function getClassPluginCode($class_name, $plugin_name)
    {
        $tmp_arr = $this->plugin_code_result[self::PLUGIN_CODE_BY_CLASS];
        $code_arr = isset($tmp_arr[$class_name]) ? $tmp_arr[$class_name] : array();
        return isset($code_arr[$plugin_name]) ? $code_arr[$plugin_name] : '';
    }

    /**
     * 获取一个xml文件的插件代码
     * @param string $file_name
     * @param string $plugin_name
     * @return string
     */
    public function getXmlPluginCode($file_name, $plugin_name)
    {
        $tmp_arr = $this->plugin_code_result[self::PLUGIN_CODE_BY_XML];
        $code_arr = isset($tmp_arr[$file_name]) ? $tmp_arr[$file_name] : array();
        return isset($code_arr[$plugin_name]) ? $code_arr[$plugin_name] : '';
    }

    /**
     * 获取插件代码 - 结束阶段
     * @param string $plugin_name
     * @return string
     */
    public function getFinishPluginCode($plugin_name)
    {
        $code_arr = $this->plugin_code_result[self::PLUGIN_CODE_FINISH];
        return isset($code_arr[$plugin_name]) ? $code_arr[$plugin_name] : '';
    }

    /**
     * 获取所有插件代码 - 开始阶段
     * @return array
     */
    public function getBeginPluginCodeAll()
    {
        return $this->plugin_code_result[self::PLUGIN_CODE_BEGIN];
    }

    /**
     * 获取所有插件代码 - 结束阶段
     * @return array
     */
    public function getFinishPluginCodeAll()
    {
        return $this->plugin_code_result[self::PLUGIN_CODE_FINISH];
    }

    /**
     * 获取一个类 所有的插件代码
     * @param string $class_name
     * @return array
     */
    public function getClassPluginCodeAll($class_name)
    {
        $tmp_arr = $this->plugin_code_result[self::PLUGIN_CODE_BY_CLASS];
        return isset($tmp_arr[$class_name]) ? $tmp_arr[$class_name] : array();
    }

    /**
     * 获取一个xml文件的插件代码
     * @param string $file_name
     * @return array
     */
    public function getXmlPluginCodeAll($file_name)
    {
        $tmp_arr = $this->plugin_code_result[self::PLUGIN_CODE_BY_XML];
        return isset($tmp_arr[$file_name]) ? $tmp_arr[$file_name] : array();
    }

    /**
     * 获取代码生成对象
     * @return GenerateInterface
     * @throws DOPException
     */
    private function getGenerator()
    {
        $code_type = $this->build_opt->getCodeType();
        switch ($code_type) {
            case BuildOption::BUILD_CODE_PHP:
                $tmp_object = new Generator();
                break;
            default:
                throw new DOPException('Unknown code_type:' . $code_type);
        }
        return $tmp_object;
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
     * 是否需要生成Encode方法
     * @param int $type
     * @return bool
     */
    public function isBuildPackMethod($type)
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
    public function isBuildUnpackMethod($type)
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
}
