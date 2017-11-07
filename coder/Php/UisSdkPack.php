<?php

namespace FFan\Dop\Coder\Php;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\FileBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Build\StrBuf;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\Struct;
use FFan\Std\Common\Str as FFanStr;

/**
 * Class ArrayPack
 * @package FFan\Dop\Coder\Php
 */
class UisSdkPack extends PackerBase
{
    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return array('fix');
    }

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

        //@todo 以下代码太难理解， 待优化
        $response_node = $node->nextSibling;
        //如果有response, 生成获取response结果
        while (null !== $response_node) {
            if (XML_ELEMENT_NODE !== $response_node->nodeType || 'response' !== $response_node->nodeName) {
                $response_node = $response_node->nextSibling;
                continue;
            }
            $action_node = $node->parentNode;
            $name = FFanStr::camelName($action_node->getAttribute('name'));
            $class_name_suffix = $this->coder->getBuildOption()->getConfig(Struct::getTypeName(Struct::TYPE_RESPONSE) . '_class_suffix');
            $response_class_name = $name . FFanStr::camelName($class_name_suffix);
            $response_struct = $this->coder->getManager()->getStruct('/' . $struct->getFile(false) . '/' . $response_class_name);
            if ($response_struct) {
                $this->buildGetResult($response_struct, $method_buf);
            }
            break;
        }
    }

    /**
     * @param Struct $struct
     * @param CodeBuf $method_buf
     */
    public function buildGetResult($struct, $method_buf)
    {
        if (!$method_buf) {
            return;
        }
        $method_buf->emptyLine();
        $return_buf = new StrBuf();
        $import_buf = $this->file_buf->getBuf(FileBuf::IMPORT_BUF);
        if ($import_buf) {
            $import_buf->pushStr('use FFan\Dop\Uis\ActionException;');
        }
        $return_buf->pushStr(' * @return ');
        $method_buf->pushStr('/**');
        $method_buf->pushStr(' * 获取返回的结果');
        $method_buf->pushStr(' * @param int $result_mode 模式：默认，严格，兼容 三种模式');
        $method_buf->pushStr(' * @param int $success_status 成功的status');
        $method_buf->push($return_buf);
        $method_buf->pushStr(' * @throws ActionException');
        $method_buf->pushStr(' */');
        $method_buf->pushStr('public function getResult($result_mode = HttpClient::DEFAULT_MODE, $success_status = 200)');
        $method_buf->pushStr('{')->indent();
        $method_buf->pushStr('$api_result = $this->getResponse();');
        $method_buf->pushStr('if (HttpClient::STRICT_MODE === $result_mode && $success_status !== $api_result->status) {')->indent();
        $method_buf->pushStr('self::fixErrorMessage($api_result);');
        $method_buf->pushStr('throw new ActionException($api_result->message, $api_result->status);');
        $method_buf->backIndent()->pushStr('}');
        $method_buf->pushStr('$result = new ' . $struct->getClassName() . '();');
        $all_item = $struct->getAllExtendItem();
        $method_buf->pushStr('if ($success_status === $api_result->status) {')->indent();
        $method_buf->pushStr('$data = $this->getResponseData();');
        $method_buf->pushStr('$result->arrayUnpack($data);');
        $method_buf->backIndent()->pushStr('} elseif (HttpClient::COMPATIBLE_MODE === $result_mode) {')->indent();
        $method_buf->pushStr('$result->fixNullData();');
        $method_buf->backIndent()->pushStr('}');
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
