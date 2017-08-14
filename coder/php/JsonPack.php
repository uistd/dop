<?php

namespace ffan\dop\coder\php;
use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\protocol\Struct;

/**
 * Class JsonPack
 * @package ffan\dop\pack\php
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
        if ($struct->isSubStruct()) {
            return;
        }
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 生成json串');
        $code_buf->pushStr(' * @return string');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function jsonPack()');
        $code_buf->pushStr('{');
        $code_buf->indent();
        $code_buf->pushStr('$data = $this->arrayPack();');
        $code_buf->pushStr('$result = json_encode($data, JSON_UNESCAPED_UNICODE);');
        $code_buf->pushStr('if (JSON_ERROR_NONE !== json_last_error()) {');
        $code_buf->pushIndent('$result = \'\';');
        $code_buf->pushStr('}');
        $code_buf->pushStr('return $result;');
        $code_buf->backIndent();
        $code_buf->pushStr('}');
    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        if ($struct->isSubStruct()) {
            return;
        }
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 对象初始化');
        $code_buf->pushStr(' * @param string $json_raw');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function jsonUnpack($json_raw)');
        $code_buf->pushStr('{');
        $code_buf->indent();
        $code_buf->pushStr('$data = json_decode($json_raw, true);');
        $code_buf->pushStr('if (JSON_ERROR_NONE !== json_last_error()) {');
        $code_buf->pushIndent('$data = array();');
        $code_buf->pushStr('}');
        $code_buf->pushStr('$this->arrayUnpack($data);');
        $code_buf->backIndent();
        $code_buf->pushStr('}');
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