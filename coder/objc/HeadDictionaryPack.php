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
}
