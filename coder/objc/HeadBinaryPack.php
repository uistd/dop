<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\PackerBase;
use ffan\dop\protocol\Struct;

/**
 * Class HeadBinaryPack
 * @package ffan\dop\coder\objc
 */
class HeadBinaryPack extends PackerBase
{
    /**
     * 获取依赖的packer
     * @return null|array
     */
    public function getRequirePacker()
    {
        return array('struct', 'dictionary');
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
        $code_buf->pushStr(' * 二进制打包');
        $code_buf->pushStr(' */');
        //如果是子 struct
        if ($struct->isSubStruct()) {
            $this->pushImportCode('@class FFANDOPEncode;');
            $code_buf->pushStr('- (void)binaryPack:(FFANDOPEncode *) result;');
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

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public function buildUnpackMethod($struct, $code_buf)
    {
        if (!$struct->isSubStruct()) {
            $code_buf->emptyLine();
            $code_buf->pushStr('/**');
            $code_buf->pushStr(' * 二进制解包');
            $code_buf->pushStr(' */');
            $code_buf->pushStr('- (int)binaryDecode:(NSData *)data;');
            $code_buf->emptyLine();
            $code_buf->pushStr('- (int)binaryDecode:(NSData *)data mask_key:(NSString*)mask_key;');
        }
    }
}