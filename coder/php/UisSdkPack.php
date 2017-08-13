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
        if (!empty($uri)) {
            $uri = trim($uri);
            $property_buf->emptyLine();
            $property_buf->pushStr('/**');
            $property_buf->pushStr(' * @var string api gateway资源地址');
            $property_buf->pushStr(' */');
            $property_buf->pushStr('protected static $api_gateway_uri = '. escapeshellarg($uri) .';');
        }
        $method = $node->getAttribute('method');
        if (!empty($method)) {
            $method = strtolower(trim($method));
            if ('get' !== $method) {
                $property_buf->emptyLine();
                $property_buf->pushStr('/**');
                $property_buf->pushStr(' * @var string 方法');
                $property_buf->pushStr(' */');
                $property_buf->pushStr('protected static $api_gateway_method = '. escapeshellarg($method) .';');
            }
        }
        $response_node = $node->nextSibling;
        //如果有response, 生成获取response结果
        while(null !== $response_node) {
            if (XML_ELEMENT_NODE === $response_node->nodeType && 'response' === $response_node->nodeName) {
                $action_node = $node->parentNode;
                $name = FFanStr::camelName($action_node->getAttribute('name'));
                $class_name_suffix = $this->coder->getBuildOption()->getConfig(Struct::getTypeName(Struct::TYPE_RESPONSE) .'_class_suffix');
                $parent_name = $name . FFanStr::camelName($class_name_suffix);
                $method_buf->pushStr('/**');
                $method_buf->pushStr(' * 请求');
                $method_buf->pushStr(' * @return '. $parent_name);
                $method_buf->pushStr(' */');
                $method_buf->pushStr('function getResponse()');
                $method_buf->pushStr('{');
                $method_buf->pushStr('}');

                break;
            }
            $response_node = $response_node->nextSibling;
        }
    }
}