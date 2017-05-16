<?php

namespace ffan\dop\coder\php;

/**
 * Class BinaryArray 二进制
 * @package ffan\dop\coder\php
 */
class BinaryArray
{
    /**
     * Big Endian
     */
    const BIG_ENDIAN = 1;

    /**
     * Little Endian
     */
    const LITTLE_ENDIAN = 2;

    /**
     * @var string
     */
    private $bin_str = '';

    /**
     * @var int 字节序
     */
    private $endian = self::LITTLE_ENDIAN;

    /**
     * 写入一段字符串
     * @param string $str
     */
    public function writeString($str)
    {
        $len = strlen($str);
        //如果长度小于252 表示真实的长度
        if ($len < 0xfc) {
            $this->writeUnsignedChar($len);
        } //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
        elseif ($len <= 0xffff) {
            $this->writeUnsignedChar(0xfc);

        }
    }

    /**
     * 写入一个无符号byte
     * @param int $char
     */
    public function writeUnsignedChar($char)
    {
        $char = (int)$char;
        $this->bin_str .= pack('C', $char);
    }

    /**
     * 写入16位int
     * @param int $short
     */
    public function writeShort($short)
    {
        $short = (int)$short;
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'v' : 'n';
        $this->bin_str .= pack($pack_arg, $short);
    }

    /**
     * 写入32位 int
     * @param int $int
     */
    public function writeInt($int)
    {
        $int = (int)$int;
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'V' : 'N';
        $this->bin_str .= pack($pack_arg, $int);
    }

    /**
     * 写入64位 int
     * @param int $bigint
     */
    public function writeBigInt($bigint)
    {
        $bigint = (int)$bigint;
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $this->bin_str .= pack($pack_arg, $bigint);
    }

    /**
     * 导出二进制
     * @return string
     */
    public function dump()
    {
        $result = $this->bin_str;
        $this->bin_str = null;
        return $result;
    }

    /**
     * 导出成hex串
     * @return string
     */
    public function dumpHex()
    {
        $result = $this->dump();
        return bin2hex($result);
    }

    /**
     * 导出成base64
     * @return string
     */
    public function dumpBase64()
    {
        $result = $this->dump();
        return base64_encode($result);
    }
}