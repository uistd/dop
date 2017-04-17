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

    protected $build_base_path;

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
    }

    /**
     * 插件代码入口
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    public function pluginCode($build_opt, $code_buf, $struct)
    {
        $plugin_list = $this->manager->getPluginList();
        if (null === $plugin_list) {
            return;
        }
        /**
         * @var string $name
         * @var Plugin $plugin
         */
        foreach ($plugin_list as $name => $plugin) {
            if (!$this->build_opt->usePlugin($name)) {
                continue;
            }
            $plugin->generateCode($build_opt, $code_buf, $struct);
        }
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
        $generator = $this->getGenerator();
        $generator->generateBegin($this);
        $use_cache = $this->build_opt->allow_cache;
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            if ($use_cache && $struct->isCached()) {
                continue;
            }
            $generator->generateByClass($this, $struct);
        }
        $file_list = $use_cache ? $this->manager->getBuildFileList() : $this->manager->getAllFileList();
        foreach ($file_list as $file) {
            $generator->generateByXml($this, $file);
        }
        $generator->generateFinish($this);
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
                throw new DOPException('Unknown code_type:'. $code_type);
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
    protected function isBuildUnpackMethod($type)
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
