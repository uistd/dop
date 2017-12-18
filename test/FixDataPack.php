<?php
namespace UiStd\Dop\Coder\Php;

class FixDataPack extends \UiStd\Dop\Build\PackerBase
{
    public function buildPackMethod($struct, $code_buf)
    {
        $code_buf->pushStr('//this is test code');
    }
}