<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\build\StrBuf;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\Struct;

/**
 * Class ArrayPack
 * @package ffan\dop\coder\php
 */
class UisSdkPack extends PackerBase
{
    /**
     * 生成定制的方法
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        if ($struct->isSubStruct() || Struct::TYPE_REQUEST !== $struct->getType()) {
            return;
        }
        $method_buf = $this->file_buf->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $this->file_buf->getBuf(FileBuf::PROPERTY_BUF);
        if (!$method_buf || !$property_buf) {
            return;
        }
        $node = $struct->getNode();
        $uri = $node->getAttribute('uri');
        $method = $node->getAttribute('method');
        $method_buf->emptyLine();
        $method_buf->pushStr('/**');
        $method_buf->pushStr(' * @param string $uri');
        $method_buf->pushStr(' * @param string $method');
        $method_buf->pushStr(' */');
        $uri_param = '$uri';
        if (!empty($uri)) {
            $uri_param .= " = '" . $uri . "'";
        }
        $method_param = '$method';
        if (empty($method)) {
            $method = 'get';
        }
        $method_param .= " = '" . $method . "'";
        $method_buf->pushStr('public function __construct(' . $uri_param . ', ' . $method_param . ')');
        $method_buf->pushStr('{')->indent();
        $method_buf->pushStr('parent::__construct($uri, $method);');
        $method_buf->backIndent()->pushStr('}');
    }

    /**
     * @param Struct $struct
     * @param CodeBuf $code_buf
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        if ($struct->isSubStruct() || Struct::TYPE_RESPONSE !== $struct->getType()) {
            return;
        }
        $method_buf = $this->file_buf->getBuf(FileBuf::METHOD_BUF);
        if (!$method_buf) {
            return;
        }
        $method_buf->emptyLine();
        $return_buf = new StrBuf();
        $return_buf->pushStr(' * @return ');
        $method_buf->pushStr('/**');
        $method_buf->pushStr(' * 获取返回的结果');
        $method_buf->push($return_buf);
        $method_buf->pushStr(' */');
        $method_buf->pushStr('public function getResult()');
        $method_buf->pushStr('{')->indent();
        $method_buf->pushStr('$result = new ' . $struct->getClassName() . '();');
        $all_item = $struct->getAllExtendItem();
        $method_buf->pushStr('$data = $this->getResponseData();');
        $method_buf->pushStr('$result->arrayUnpack($data);');
        if (isset($all_item['status'], $all_item['message'], $all_item['data'])) {
            /** @var Item $data_item */
            $data_item = $all_item['data'];
            //如果只包含3个属性， 表示为标准输出
            unset($all_item['status'], $all_item['message'], $all_item['data']);
            //只有3个属性
            if (empty($all_item)) {
                $return_buf->pushStr(Coder::varType($data_item));
                $method_buf->pushStr('return $result->data;');
            } else {
                $return_buf->pushStr($struct->getClassName());
                $method_buf->pushStr('return $result;');
            }
        } else {
            $return_buf->pushStr($struct->getClassName());
            $method_buf->pushStr('return $result;');
        }
        $method_buf->backIndent()->pushStr('}');
    }
}
