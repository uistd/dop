<?php

namespace {{$namespace}};

/**
 * Class BinaryBuffer PHP二进制操作类
 * @package {{$namespace}}
 */
class BinaryBuffer
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
     *
     * @var string
     */
    private $bin_str = '';

    /**
     * @var int 字节序
     */
    private $endian = self::LITTLE_ENDIAN;

    /**
     * @var int 读数据的位置
     */
    private $read_pos = 0;

    /**
     * @var int
     */
    private $max_read_pos = 0;

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
            $this->writeShort($len);
        } //4GB长度字符串
        elseif ($len <= 0xffffffff) {
            $this->writeUnsignedChar(0xfe);
            $this->writeInt($len);
        } else {
            $this->writeUnsignedChar(0xff);
            $this->writeBigInt($len);
        }
    }

    /**
     * 写入一个有符号char
     * @param int $char
     */
    public function writeChar($char)
    {
        $this->bin_str .= pack('c', $char);
        ++$this->max_read_pos;
    }

    /**
     * 写入一个无符号byte
     * @param int $char
     */
    public function writeUnsignedChar($char)
    {
        $this->bin_str .= pack('C', $char);
        ++$this->max_read_pos;
    }

    /**
     * 写入16位int
     * @param int $short
     */
    public function writeShort($short)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'v' : 'n';
        $this->bin_str .= pack($pack_arg, $short);
        $this->max_read_pos += 2;
    }

    /**
     * 写入32位 int
     * @param int $int
     */
    public function writeInt($int)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'V' : 'N';
        $this->bin_str .= pack($pack_arg, $int);
        $this->max_read_pos += 4;
    }

    /**
     * 写入64位 int
     * @param int $bigint
     */
    public function writeBigInt($bigint)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $this->bin_str .= pack($pack_arg, $bigint);
        $this->max_read_pos += 8;
    }

    /**
     * 读出一个char
     * @return int
     */
    public function readChar()
    {
        if ($this->read_pos <= $this->max_read_pos) {
            return 0;
        }
        $result = unpack('cre', $this->bin_str{$this->read_pos++});
        return $result['re'];
    }

    /**
     * 读出一个unsigned char
     * @return int
     */
    public function readUnsignedChar()
    {
        if ($this->read_pos <= $this->max_read_pos) {
            return 0;
        }
        $result = unpack('Cre', $this->bin_str{$this->read_pos++});
        return $result['re'];
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readShort()
    {
        $result = $this->readUnsignedShort();
        if ($result > 0x7fff) {
            $result = (0xffff - $result + 1) * -1;
        }
        return $result;
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readUnsignedShort()
    {
        if ($this->max_read_pos - $this->read_pos < 2) {
            $this->read_pos = $this->max_read_pos;
            return 0;
        }
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'v' : 'n';
        $sub_str = substr($this->bin_str, $this->read_pos, 2);
        $this->read_pos += 2;
        $result = unpack($pack_arg . 're', $sub_str);
        return $result['re'];
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readInt()
    {
        $result = $this->readUnsignedInt();
        if ($result > 0x7fffffff) {
            $result = (0xffffffff - $result + 1) * -1;
        }
        return $result;
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readUnsignedInt()
    {
        if ($this->max_read_pos - $this->read_pos < 4) {
            $this->read_pos = $this->max_read_pos;
            return 0;
        }
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'V' : 'N';
        $sub_str = substr($this->bin_str, $this->read_pos, 4);
        $this->read_pos += 4;
        $result = unpack($pack_arg . 're', $sub_str);
        return $result['re'];
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readBigInt()
    {
        $result = $this->readUnsignedBigInt();
        if ($result > 0x7fffffffffffffff) {
            $result = (0xffffffffffffffff - $result + 1) * -1;
        }
        return $result;
    }

    /**
     * 读出一个16位有符号 int
     * @return int
     */
    public function readUnsignedBigInt()
    {
        if ($this->max_read_pos - $this->read_pos < 8) {
            $this->read_pos = $this->max_read_pos;
            return 0;
        }
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $sub_str = substr($this->bin_str, $this->read_pos, 8);
        $this->read_pos += 8;
        $result = unpack($pack_arg . 're', $sub_str);
        return $result['re'];
    }

    /**
     * 读取一段字符串
     * @return string
     */
    public function readString()
    {
        $len = $this->readUnsignedChar();
        if (0 === $len) {
            return '';
        }
        if ($len < 0xfc) {
            $str_len = $len;
        } elseif ($len === 0xfc) {
            $str_len = $this->readUnsignedShort();
        } elseif ($len === 0xfe) {
            $str_len = $this->readUnsignedInt();
        } else {
            $str_len = $this->readUnsignedBigInt();
        }
        //长度 不够
        if ($this->max_read_pos - $this->read_pos < $str_len) {
            $this->read_pos = $this->max_read_pos;
            return '';
        }
        $result = substr($this->bin_str, $this->read_pos, $str_len);
        $this->read_pos += $str_len;
        return $result;
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
