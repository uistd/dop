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
        Tpl::registerGrep('item_type_name', array('ffan\\dop\\ItemType', 'getTypeName'));
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
     * 生成文件
     * @param string $namespace 命令空间
     * @param array[Struct]$class_list
     */
    private function generateFile($namespace, $class_list)
    {
        $base_path = $this->buildBasePath();
        $build_path = FFanUtils::joinPath($base_path, $namespace);
        FFanUtils::pathWriteCheck($build_path);
        /**
         * @var string $class_name
         * @var Struct $struct
         */
        foreach ($class_list as $class_name => $struct) {
            $tpl_data = $this->buildTplData();
            $tpl_data['struct' ] = $struct->export();
            $tpl_data['class_name' ] = $class_name;
            $result = Tpl::get($this->tpl, $tpl_data);
            echo $result, PHP_EOL;
        }
    }
}
