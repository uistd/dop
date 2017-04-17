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
     * @param Plugin $plugin
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     * @return void
     */
    public static function pluginCode(Plugin $plugin, BuildOption $build_opt, CodeBuf $code_buf, Struct $struct);

    /**
     * 通用代码生成
     * @param Plugin $plugin
     * @param BuildOption $build_opt
     * @return void
     */
    public static function commonCode(Plugin $plugin, BuildOption $build_opt);
}
