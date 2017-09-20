<?php

namespace FFan\Dop\Coder\Objc;

use FFan\Dop\Build\PackerBase;

/**
 * Class HeadJsonPack header代码生成
 * @package FFan\Dop\Coder\Ojbc
 */
class HeadJsonPack extends PackerBase
{
    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return array('dictionary');
    }

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
        if (!$struct->isSubStruct()) {
            $code_buf->pushStr('- (BOOL)jsonDecode:(NSString*)json_str;');
        }
    }
}
