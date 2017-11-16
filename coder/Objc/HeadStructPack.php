<?php

namespace FFan\Dop\Coder\Objc;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\PackerBase;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * Class StructPack
 * @package FFan\Dop\Coder\Ojbc
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
