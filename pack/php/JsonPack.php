<?php

namespace ffan\dop\pack\php;

use ffan\dop\CodeBuf;
use ffan\dop\PackerBase;
use ffan\dop\Struct;

/**
 * Class JsonPack
 * @package ffan\dop\php
 */
class JsonPack extends PackerBase
{
    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 生成json串');
        $code_buf->push(' * @return string');
        $code_buf->push(' */');
        $code_buf->push('public function jsonPack()');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $code_buf->push('$data = $this->arrayPack();');
        $code_buf->push('$result = json_encode($data, JSON_UNESCAPED_UNICODE);');
        $code_buf->push('if (JSON_ERROR_NONE !== json_last_error()) {');
        $code_buf->pushIndent('$result = \'\';');
        $code_buf->push('}');
        $code_buf->push('return $result;');
        $code_buf->indentDecrease();
        $code_buf->push('}');
    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 对象初始化');
        $code_buf->push(' * @param string $json_raw');
        $code_buf->push(' */');
        $code_buf->push('public function jsonUnpack($json_raw)');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $code_buf->push('$data = json_decode($json_raw, true);');
        $code_buf->push('if (JSON_ERROR_NONE !== json_last_error()) {');
        $code_buf->pushIndent('$data = array();');
        $code_buf->push('}');
        $code_buf->push('$this->arrayUnpack($data);');
        $code_buf->indentDecrease();
        $code_buf->push('}');
    }

    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return array('array');
    }
}