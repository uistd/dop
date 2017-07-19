<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\PackerBase;

/**
 * Class HeadJsonPack header代码生成
 * @package ffan\dop\coder\objc
 */
class HeadJsonPack extends PackerBase
{
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * json encode');
        $code_buf->pushStr(' */');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (NSMutableDictionary*) jsonEncode;');
        } else {
            $code_buf->pushStr('- (NSString *)jsonEncode;');
        }
    }

    public function buildUnpackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->pushStr('/**');
        $code_buf->pushStr(' * json encode');
        $code_buf->pushStr(' */');
        if ($struct->isSubStruct()) {
            $code_buf->pushStr('- (void)jsonDecode:(NSDictionary*)json_dict;');
        } else {
            $code_buf->pushStr('- (BOOL)jsonDecode:(NSString*)json_str;');
        }
    }
}
