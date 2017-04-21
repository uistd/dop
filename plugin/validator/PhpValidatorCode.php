<?php

namespace ffan\dop\plugin\validator;

use ffan\dop\CodeBuf;
use ffan\dop\FileBuf;
use ffan\dop\PluginCoder;
use ffan\dop\Struct;

/**
 * Class PhpValidatorCode
 * @package ffan\dop\plugin\validator
 */
class PhpValidatorCode extends PluginCoder
{
    /**
     * 生成validate方法
     * @param Struct $struct
     * @param FileBuf $file_buf
     */
    public function buildStructCode($struct, FileBuf $file_buf)
    {
        $code_buf = new CodeBuf();
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 验证数据是否正确');
        $code_buf->push(' */');
    }
}