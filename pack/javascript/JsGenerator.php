<?php

namespace ffan\dop\pack\js;

use ffan\dop\Exception;
use ffan\dop\Builder;
use ffan\dop\Struct;

/**
 * Class JsGenerator
 * @package ffan\dop
 */
class JsGenerator extends Builder
{
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
     * @throws Exception
     */
    protected function generateFile($namespace, $class_list)
    {
        // TODO: Implement generateFile() method.
    }
}
