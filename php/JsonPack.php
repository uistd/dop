<?php

namespace ffan\dop\php;

use ffan\dop\CodeBuf;
use ffan\dop\PackInterface;
use ffan\dop\Struct;

/**
 * Class JsonPack
 * @package ffan\dop\php
 */
class JsonPack implements PackInterface
{
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildPackMethod($struct, $code_buf)
    {

    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildUnPackMethod($struct, $code_buf)
    {
        $method_name = 'jsonUnpack';
        if (!$code_buf->addMethod($method_name)) {
            return;
        }
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 对象初始化');
        $code_buf->push(' * @param string $json_raw');
        $code_buf->push(' */');
        $code_buf->push('public function ' . $method_name . '($json_raw)');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $code_buf->push('$data = json_decode($json_raw, true);');
        $code_buf->push('if (JSON_ERROR_NONE !== json_last_error()) {');
        $code_buf->indentIncrease();
        $code_buf->push('$data = array();');
        $code_buf->indentDecrease();
        $code_buf->push('}');
        $code_buf->push('$this->arrayUnpack($data);');
        $code_buf->indentDecrease();
        $code_buf->push('}');
        //把数组方法打包进去
        ArrayPack::buildUnPackMethod($struct, $code_buf);
    }
}