<?php
namespace FFan\Dop\Coder\Php;

class FixDataPack extends \FFan\Dop\Build\PackerBase
{
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->pushStr('//this is test code');
    }
}