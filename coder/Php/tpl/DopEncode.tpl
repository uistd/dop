<?php

namespace {{$namespace}};

/**
 * Class DopEncode PHP二进制协议打包类
 * @package {{$namespace}}
 */
class DopEncode
{
    /**
     * 标志位：带数据ID
     */
    const OPTION_PID = 0x1;

    /**
     * 标志位：数据签名
     */
    const OPTION_SIGN = 0x2;

    /**
     * 标志位：数据加密
     */
    const OPTION_MASK = 0x4;

    /**
     * 标志位：是否是big endian
     */
    const OPTION_ENDIAN = 0x8;

    /**
     * 签名字符串长度
     */
    const SIGN_CODE_LEN = 8;

    /**
     * 加密key最小长度
     */
    const MIN_MASK_KEY_LEN = 8;

    /**
     * @var string
     */
    private $bin_str = '';

    /**
     * @var bool 是否大字节序
     */
    private static $is_big_endian;

    /**
     * @var int 标志位
     */
    private $opt_flag;

    /**
     * @var int pid的长度
     */
    private $pid_len = 0;

    /**
     * @var string 加密key
     */
    private $mask_key;
    
    /**
     * 是否大字节序
     * @return bool
     */
    public static function isBigEndian()
    {
        if (null === self::$is_big_endian) {
            self::$is_big_endian = pack('L', 1) === pack('N', 1);
        }
        return self::$is_big_endian;
    }
    
    /**
     * 写入pid
     * @param string $pid
     */
    public function writePid($pid)
    {
        $this->opt_flag |= self::OPTION_PID;
        $this->writeString($pid);
        $this->pid_len = strlen($this->bin_str);
    }

    /**
     * 数据签名
     */
    public function sign()
    {
        $this->opt_flag |= self::OPTION_SIGN;
    }

    /**
     * 数据加密
     * @param string $mask_key
     */
    public function mask($mask_key)
    {
        $this->mask_key = $mask_key;
        $this->opt_flag |= self::OPTION_MASK;
    }

    /**
     * 写入一段字符串
     * @param string $str
     */
    public function writeString($str)
    {
        if (!is_string($str)) {
            $this->writeLength(0);
        } else {
            $len = strlen($str);
            $this->writeLength($len);
            $this->bin_str .= pack('a' . $len, $str);
        }
    }

    /**
     * 写入长度表示
     * @param int $len
     */
    public function writeLength($len)
    {
        //如果长度小于252 表示真实的长度
        if ($len < 0xfc) {
            $this->writeChar($len);
        } //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
        elseif ($len <= 0xffff) {
            $this->writeChar(0xfc);
            $this->writeShort($len);
        } //如果长度小于等于4GB，先写入 0xfe，后面再写入两位表示字符串长度
        elseif ($len <= 0xffffffff) {
            $this->writeChar(0xfe);
            $this->writeInt($len);
        } //更大
        else {
            $this->writeChar(0xff);
            $this->writeBigInt($len);
        }
    }
    
    /**
     * 写入一个符号char
     * @param int $char
     */
    public function writeChar($char)
    {
        $arg = $char >= 0 ? 'C' : 'c';
        $this->bin_str .= pack($arg, $char);
    }

    /**
     * 写入16位int
     * @param int $short
     */
    public function writeShort($short)
    {
        $this->bin_str .= pack('S', (int)$short);
    }

    /**
     * 写入32位 int
     * @param int $int
     */
    public function writeInt($int)
    {
        $this->bin_str .= pack('L', (int)$int);
    }

    /**
     * 写入64位 int
     * @param int $bigint
     */
    public function writeBigInt($bigint)
    {
        $this->bin_str .= pack('Q', (int)$bigint);
    }

    /**
     * 写入符点数
     * @param float $value
     */
    public function writeFloat($value)
    {
        $this->bin_str .= pack('f', (float)$value);
    }

    /**
     * 写入双精度符点数
     * @param float $value
     */
    public function writeDouble($value)
    {
        $this->bin_str .= pack('d', $value);
    }

    /**
     * 将两个buffer连接
     * @param DopEncode $sub_buffer
     */
    public function joinBuffer($sub_buffer)
    {
        $this->bin_str .= $sub_buffer->dump();
    }

    /**
     * 数据加密
     * @param int $begin_pos 开始位置
     */
    private function doMask($begin_pos)
    {
        $mask_key = self::fixMaskKey($this->mask_key);
        $mask_len = strlen($mask_key);
        $data_size = strlen($this->bin_str);
        $pos = 0;
        for ($i = $begin_pos; $i < $data_size; ++$i) {
            $index = $pos++ % $mask_len;
            $this->bin_str{$i} = $this->bin_str{$i} ^ $mask_key{$index};
        }
    }

    /**
     * 导出二进制
     * @return string
     */
    public function dump()
    {
        return $this->bin_str;
    }
    
    /**
     * 生成签名串
     * @param string $bin_str 二进制内容
     * @return string
     */
    public static function makeSignCode($bin_str)
    {
        return substr(md5($bin_str), 0, self::SIGN_CODE_LEN);
    }

    /**
     * 修正加密串
     * @param string $mask_key
     * @return string
     */
    public static function fixMaskKey($mask_key)
    {
        if (strlen($mask_key) < self::MIN_MASK_KEY_LEN) {
            $mask_key = md5($mask_key);
        }
        return $mask_key;
    }

    /**
     * 打包成最终的结果
     * @return string
     */
    public function pack()
    {
        if ($this->opt_flag & self::OPTION_SIGN) {
            $this->bin_str .= self::makeSignCode($this->bin_str);
        }
        if ($this->opt_flag & self::OPTION_MASK) {
            $this->doMask($this->pid_len);
        }
        if (pack('L', 1) === pack('N', 1)) {
            $this->opt_flag |= self::OPTION_ENDIAN;
        }
        $tmp_str = $this->bin_str;
        $result_len = strlen($tmp_str);
        $this->bin_str = '';
        //写入标志位
        $this->writeChar($this->opt_flag);
        //写入长度
        $this->writeLength($result_len);
        $this->bin_str .= $tmp_str;
        return $this->bin_str;
    }
}
