<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PackerBase;
use ffan\php\utils\Str as FFanStr;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

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
            $uri_param .= " = '". $uri ."'";
        }
        $method_param = '$method';
        if (!empty($method)) {
            $method_param .= " = '". $method ."'";
        }
        $method_buf->pushStr('public function __construct('. $uri_param .', '. $method_param .')');
        $method_buf->pushStr('{')->indent();
        $method_buf->pushStr('parent::__construct($uri, $method);');
        $method_buf->backIndent()->pushStr('}')->emptyLine();

        $response_node = $node->nextSibling;
        //如果有response, 生成获取response结果
        while(null !== $response_node) {
            if (XML_ELEMENT_NODE === $response_node->nodeType && 'response' === $response_node->nodeName) {
                $action_node = $node->parentNode;
                $name = FFanStr::camelName($action_node->getAttribute('name'));
                $class_name_suffix = $this->coder->getBuildOption()->getConfig(Struct::getTypeName(Struct::TYPE_RESPONSE) .'_class_suffix');
                $response_class_name = $name . FFanStr::camelName($class_name_suffix);
                $method_buf->pushStr('/**');
                $method_buf->pushStr(' * 获取请求结果');
                $method_buf->pushStr(' * @return '. $response_class_name);
                $method_buf->pushStr(' */');
                $method_buf->pushStr('public function request()');
                $method_buf->pushStr('{')->indent();
                $method_buf->pushStr('$data = $this->getResponseData();');
                $method_buf->pushStr('$result = new '. $response_class_name .'();');
                $method_buf->pushStr('$result->arrayUnpack($data);');
                $method_buf->pushStr('return $result;');
                $method_buf->backIndent()->pushStr('}');
                break;
            }
            $response_node = $response_node->nextSibling;
        }
    }
}