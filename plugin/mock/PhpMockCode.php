<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\plugin\PluginCode;
use ffan\dop\Struct;

/**
 * Class PhpMockCode
 * @package ffan\dop\plugin\mock
 */
class PhpMockCode implements PluginCode
{

    /**
     * PHP 相关插件代码
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    public static function pluginCode(BuildOption $build_opt, CodeBuf $code_buf, Struct $struct)
    {
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 生成mock数据');
        $code_buf->push(' */');
        $code_buf->push('public function mock()');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $code_buf->indentDecrease()->push('}');
    }
}
