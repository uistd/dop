<?php
namespace ffan\dop;

/**
 * Class JsGenerator
 * @package ffan\dop
 */
class JsGenerator extends DOPGenerator
{

    /**
     * 整理生成文件的参数
     * @return array
     */
    protected function buildTplData()
    {
        // TODO: Implement buildTplData() method.
    }

    /**
     * 生成文件名
     * @param string $build_path
     * @param Struct $struct
     * @return string
     */
    protected function buildFileName($build_path, Struct $struct)
    {
        $class_name = $struct->getClassName();
        return $build_path . $class_name . '.js';
    }

    /**
     * 生成文件
     * @param string $namespace 命令空间
     * @param array [Struct] $class_list
     * @param array $tpl_data 模板数据
     * @throws DOPException
     */
    protected function generateFile($namespace, $class_list, $tpl_data)
    {
        // TODO: Implement generateFile() method.
    }
}
