<?php

namespace UiStd\Dop\Coder\Java;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\Struct;

/**
 * Class Fix 修正数据
 * @package UiStd\Dop\Coder\Java
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
        $code_buf->pushStr(' * 数据修正');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('public void fixGsonResult() {')->indent();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $type = $item->getType();
            switch ($type) {
                case ItemType::STRING:
                    $code_buf->pushStr('if (null == this.'. $name .') {');
                    $code_buf->pushIndent('this.'. $name .' = "";');
                    $code_buf->pushStr('}');
                    break;
                case ItemType::BINARY:
                    $code_buf->pushStr('if (null == this.'. $name .') {');
                    $code_buf->pushIndent('this.'. $name .' = new byte[0];');
                    $code_buf->pushStr('}');
                    break;
                case ItemType::ARR:
                    $list_type = Coder::varType($item, 0, false);
                    $code_buf->pushStr('if (null == this.'. $name. ') {');
                    $code_buf->pushIndent('this.'. $name. ' = new '. $list_type. '(0);');
                    $code_buf->pushStr('}');
                    break;
                    break;
                case ItemType::MAP:
                    $map_type = Coder::varType($item, 0, false);
                    $code_buf->pushStr('if (null == this.'. $name. ') {');
                    $code_buf->pushIndent('this.'. $name. ' = new '. $map_type. '(0);');
                    $code_buf->pushStr('}');
                    break;
                case ItemType::STRUCT:
                    $code_buf->pushStr('if (null != this.'. $name. ') {');
                    $code_buf->pushIndent('this.'. $name. '->fixGsonResult();');
                    $code_buf->pushStr('}');
                    break;
            }
        }
        $code_buf->backIndent()->pushStr('}');
    }
}
