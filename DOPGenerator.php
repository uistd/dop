<?php
namespace ffan\dop;

use ffan\php\tpl\Tpl;
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
     * @var string request的模板
     */
    protected $tpl;

    /**
     * @var int 文件单位
     */
    protected $file_unit;

    /**
     * Generator constructor.
     * @param ProtocolManager $protocol_manager
     */
    public function __construct(ProtocolManager $protocol_manager)
    {
        $this->protocol_manager = $protocol_manager;
        $conf_arr = array(
            'tpl_dir' => 'tpl',
            'cache_result' => false
        );
        FFanConfig::add('ffan-tpl', $conf_arr);
        //变量类型的 字符串 表示
        Tpl::registerGrep('item_type_name', array('ffan\\dop\\ItemType', 'getTypeName'));
        //生成缩进值
        Tpl::registerGrep('indent', array('ffan\\dop\\DOPGenerator', 'indentSpace'));
        //生成临时变量
        Tpl::registerGrep('tmp_var_name', array('ffan\\dop\\DOPGenerator', 'tmpVarName'));
        //插件代码
        Tpl::registerPlugin('plugin_code', array($this, 'pluginCode'));
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
            $plugin_code .= $plugin->generateCode($args);
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
        $tpl_data = $this->buildTplData();
        foreach ($protocol_list as $namespace => $class_list) {
            $this->generateFile($namespace, $class_list, $tpl_data);
        }
    }

    /**
     * 整理生成文件的参数
     * @return array
     */
    abstract protected function buildTplData();

    /**
     * 获取编译的基础目录
     * @return string
     */
    protected function buildBasePath()
    {
        return FFanUtils::fixWithRuntimePath($this->protocol_manager->getBuildPath());
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
     * @param array $tpl_data 模板数据
     * @throws DOPException
     */
    abstract protected function generateFile($namespace, $class_list, $tpl_data);
}
