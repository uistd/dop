<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\PackerBase;

/**
 * @package ffan\dop\coder\objc
 */
class HeadDictionaryPack extends PackerBase
{
    public function buildUnpackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * Dictionary 解析');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('- (void)dictionaryDecode:(NSDictionary*) dict_map;');
    }

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
        $code_buf->pushStr(' * 输出NSDictionary');
        $code_buf->pushStr(' */');
        $code_buf->pushStr('- (NSDictionary *)dictionaryEncode;');
    }
}
