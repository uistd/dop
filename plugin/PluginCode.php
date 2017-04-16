<?php

namespace ffan\dop\plugin;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\Struct;

/**
 * Interface PluginCode
 * @package ffan\dop\plugin
 */
interface PluginCode
{
    /**
     * PHP 相关插件代码
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    public static function pluginCode(BuildOption $build_opt, CodeBuf $code_buf, Struct $struct);
}
