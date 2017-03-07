<?php
namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;

/**
 * Class PhpGenerator
 * @package ffan\dop
 */
class PhpGenerator extends DOPGenerator
{

    /**
     * 整理生成文件的参数
     * @return array
     */
    protected function buildTplData()
    {
        $build_arg = array(
            'main_namespace' => $this->protocol_manager->getMainNameSpace(),
            'path_define_var' => $this->protocol_manager->getConfig('path_define_var', 'DOP_PATH'),
            'build_path' => $this->protocol_manager->getBuildPath(),
            'code_namespace' => 'namespace'
        );
        return $build_arg;
    }
}