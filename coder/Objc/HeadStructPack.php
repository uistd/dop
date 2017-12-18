<?php

namespace UiStd\Dop\Coder\Objc;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\PackerBase;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;

/**
 * Class StructPack
 * @package UiStd\Dop\Coder\Ojbc
 */
class HeadStructPack extends PackerBase
{
    /**
     * @var Coder
     */
    protected $coder;

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * 生成二进制协议头');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('+ (NSData*) binaryStruct;');
    }
}
