<?php

namespace FFan\Dop\Coder\Php;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * Class ArrayPack
 * @package FFan\Dop\Coder\Php
 */
class FixPack extends PackerBase
{
    /**
     * 修正数据
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 数据修正，保证不会有null');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public function fixNullData() {')->indent();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $name = $this->coder->fixPropertyName($name, $item);
            $type = $item->getType();
            switch ($type) {
                case ItemType::STRING:
                case ItemType::BINARY:
                    $code_buf->pushStr('if (null == $this->'. $name .') {');
                    $code_buf->pushIndent('$this->'. $name ." = '';");
                    $code_buf->pushStr('}');
                    break;
                case ItemType::ARR:
                case ItemType::MAP:
                    $code_buf->pushStr('if (null == $this->'. $name. ') {');
                    $code_buf->pushIndent('$this->'. $name. ' = array();');
                    $code_buf->pushStr('}');
                    break;
                case ItemType::INT:
                    $code_buf->pushStr('if (null == $this->'. $name .') {');
                    $code_buf->pushIndent('$this->'. $name ." = 0;");
                    $code_buf->pushStr('}');
                    break;
                case ItemType::BOOL:
                    $code_buf->pushStr('if (null == $this->'. $name .') {');
                    $code_buf->pushIndent('$this->'. $name ." = false;");
                    $code_buf->pushStr('}');
                    break;
                case ItemType::FLOAT:
                case ItemType::DOUBLE:
                    $code_buf->pushStr('if (null == $this->'. $name .') {');
                    $code_buf->pushIndent('$this->'. $name ." = 0.0;");
                    $code_buf->pushStr('}');
                    break;
                case ItemType::STRUCT:
                    /** @var StructItem $item */
                    $sub_struct = $item->getStruct();
                    $code_buf->pushStr('if (null != $this->'. $name. ') {');
                    $code_buf->pushIndent('$this->'. $name. ' = new '. $sub_struct->getClassName() .'();');
                    $code_buf->pushIndent('$this->'. $name. '->fixNullData();');
                    $code_buf->pushStr('}');
                    break;
            }
        }
        $code_buf->backIndent()->pushStr('}');
    }
}