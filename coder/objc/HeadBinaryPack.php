<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\Exception;
use ffan\dop\protocol\IntItem;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * Class HeadBinaryPack
 * @package ffan\dop\coder\objc
 */
class HeadBinaryPack extends PackerBase
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
        $code_buf->pushStr(' * 二进制打包');
        $code_buf->pushStr(' */');
        //如果是子 struct
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (void)binaryPack:(FFANDOPEncode *) result;');
            $code_buf->indent();
        } else {
            $code_buf->pushStr('- (NSData *)binaryEncode;');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid;');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid is_sign:(BOOL)is_sign;');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (NSData *)binaryEncode:(BOOL)pid mask_key:(NSString *)mask_key;');
            $code_buf->emptyLine();
        }
    }
}