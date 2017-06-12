<?php

namespace {{$namespace}};

/**
 * Class BinaryBuffer PHP二进制操作类
 * @package {{$namespace}}
 */
class BinaryBuffer
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
     * 数据长度错误
     */
    const ERROR_SIZE = 1;

    /**
     * 数据签名出错
     */
    const ERROR_SIGN_CODE = 2;
    
    /**
     * 读数据出错
     */
    const ERROR_DATA = 3;

    /**
     * 数据解密出错
     */
    const ERROR_MASK = 4;

    /**
     * Big Endian
     */
    const BIG_ENDIAN = 1;

    /**
     * Little Endian
     */
    const LITTLE_ENDIAN = 2;

    /**
     * 签名字符串长度
     */
    const SIGN_CODE_LEN = 8;

    /**
     * 加密key最小长度
     */
    const MIN_MASK_KEY_LEN = 16;

    /**
     * 读int数据的函数
     * @var array
     */
    private static $read_int_func = array(
        0x12 => 'Char',
        0x92 => 'UnsignedChar',
        0x22 => 'Short',
        0xa2 => 'UnsignedShort',
        0x42 => 'Int',
        0xc2 => 'UnsignedInt',
        0x82 => 'Bigint'
    );

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
     * @var string 数据包ID
     */
    private $pid;

    /**
     * @var int 数据打包参数
     */
    private $pack_opt;

    /**
     * @var int 错误ID
     */
    private $error_code = 0;

    /**
     * @var bool 是否已经解析完头信息
     */
    private $unpack_head = false;

    /**
     * BinaryBuffer constructor.
     * @param null|string $raw_data 初始数据
     */
    public function __construct($raw_data = null)
    {
        if (null === $raw_data || !is_string($raw_data)) {
            return;
        }
        $this->bin_str = $raw_data;
        $this->max_read_pos = strlen($raw_data);
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
            $this->max_read_pos += $len;
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
            $this->writeUnsignedChar($len);
        } //如果长度小于等于65535，先写入 0xfc，后面再写入两位表示字符串长度
        elseif ($len <= 0xffff) {
            $this->writeUnsignedChar(0xfc);
            $this->writeShort($len);
        } //如果长度小于等于4GB，先写入 0xfe，后面再写入两位表示字符串长度
        elseif ($len <= 0xffffffff) {
            $this->writeUnsignedChar(0xfe);
            $this->writeInt($len);
        } //更大
        else {
            $this->writeUnsignedChar(0xff);
            $this->writeBigInt($len);
        }
    }

    /**
     * 在最前面写入长度值
     * @param int $len
     */
    public function writeLengthAtBegin($len)
    {
        static $tmp_buff;
        if (null === $tmp_buff) {
            $tmp_buff = new self();
        }
        $tmp_buff->writeLength($len);
        $len = $tmp_buff->getLength();
        $result = $tmp_buff->dump();
        $this->bin_str = $result . $this->bin_str;
        $this->max_read_pos += $len;
        $tmp_buff->reset();
    }

    /**
     * 读出一个长度值
     * @return int
     */
    public function readLength()
    {
        $flag = $this->readUnsignedChar();
        //长度小于252 直接表示
        if ($flag < 0xfc) {
            return $flag;
        } //长度小于65535
        elseif (0xfc === $flag) {
            return $this->readUnsignedShort();
        } //长度小于4gb
        elseif (0xfe === $flag) {
            return $this->readUnsignedInt();
        } //更长
        else {
            return $this->readUnsignedBigInt();
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
        $this->bin_str .= pack('C', (int)$char);
        ++$this->max_read_pos;
    }

    /**
     * 写入16位int
     * @param int $short
     */
    public function writeShort($short)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'v' : 'n';
        $this->bin_str .= pack($pack_arg, (int)$short);
        $this->max_read_pos += 2;
    }

    /**
     * 写入32位 int
     * @param int $int
     */
    public function writeInt($int)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'V' : 'N';
        $this->bin_str .= pack($pack_arg, (int)$int);
        $this->max_read_pos += 4;
    }

    /**
     * 写入64位 int
     * @param int $bigint
     */
    public function writeBigInt($bigint)
    {
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $this->bin_str .= pack($pack_arg, (int)$bigint);
        $this->max_read_pos += 8;
    }

    /**
     * 写入符点数
     * @param float $value
     */
    public function writeFloat($value)
    {
        $this->bin_str .= pack('f', (float)$value);
        $this->max_read_pos += 4;
    }

    /**
     * 写入双精度符点数
     * @param float $value
     */
    public function writeDouble($value)
    {
        $this->bin_str .= pack('d', $value);
        $this->max_read_pos += 8;
    }

    /**
     * 读出一个char
     * @return int
     */
    public function readChar()
    {
        if ($this->read_pos >= $this->max_read_pos) {
            $this->error_code = self::ERROR_DATA;
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
        if ($this->read_pos >= $this->max_read_pos) {
            $this->error_code = self::ERROR_DATA;
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
        if (!$this->sizeCheck(2)) {
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
        if (!$this->sizeCheck(4)) {
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
        if (!$this->sizeCheck(8)) {
            return 0;
        }
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $sub_str = substr($this->bin_str, $this->read_pos, 8);
        $this->read_pos += 8;
        $result = unpack($pack_arg . 're', $sub_str);
        return $result['re'];
    }

    /**
     * 读出一个符点数
     * @return float
     */
    public function readFloat()
    {
        if (!$this->sizeCheck(4)) {
            return 0.0;
        }
        $result = unpack('fre', substr($this->bin_str, $this->read_pos, 4));
        $this->read_pos += 4;
        return $result['re'];
    }

    /**
     * 读出一个双精度符点数
     * @return float
     */
    public function readDouble()
    {
        if (!$this->sizeCheck(8)) {
            return 0.0;
        }
        $result = unpack('dre', substr($this->bin_str, $this->read_pos, 8));
        $this->read_pos += 8;
        return $result['re'];
    }

    /**
     * 检查空间是否够
     * @param int $size 需要的空间
     * @return bool
     */
    private function sizeCheck($size)
    {
        if ($this->max_read_pos - $this->read_pos < $size) {
            $this->read_pos = $this->max_read_pos;
            $this->error_code = self::ERROR_DATA;
            return false;
        }
        return true;
    }

    /**
     * 读取一段字符串
     * @return string
     */
    public function readString()
    {
        $str_len = $this->readLength();
        if (!$this->sizeCheck($str_len)) {
            return '';
        }
        $result = substr($this->bin_str, $this->read_pos, $str_len);
        $this->read_pos += $str_len;
        return $result;
    }

    /**
     * 获取长度
     * @return int
     */
    public function getLength()
    {
        return $this->max_read_pos;
    }

    /**
     * 获取可读长度
     * @return int
     */
    public function readAvailableLength()
    {
        $result = $this->max_read_pos - $this->read_pos;
        return $result > 0 ? $result : 0;
    }

    /**
     * 将两个buffer连接
     * @param BinaryBuffer $sub_buffer
     */
    public function joinBuffer($sub_buffer)
    {
        $this->bin_str .= $sub_buffer->dump();
        $this->max_read_pos += $sub_buffer->getLength();
    }

    /**
     * 数据签名
     */
    public function sign()
    {
        $sign_code = $this->makeSignCode($this->bin_str);
        $this->bin_str .= $sign_code;
        $this->max_read_pos += self::SIGN_CODE_LEN;
    }

    /**
     * 数据加密
     * @param string $mask_key
     */
    public function mask($mask_key)
    {
        $this->doMask(1, $mask_key);
    }
    
    /**
     * 数据加密
     * @param int $beg_pos
     * @param string $mask_key
     */
    private function doMask($beg_pos, $mask_key)
    {
        if (strlen($mask_key) < self::MIN_MASK_KEY_LEN) {
            $mask_key = md5($mask_key);
        }
        $mask_len = strlen($mask_key);
        //第一位不mask
        for ($i = $beg_pos; $i < $this->max_read_pos; ++$i){
            $index = $i % $mask_len;
            $this->bin_str{$i} ^= $mask_key{$index};
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
     * 重置
     */
    public function reset()
    {
        $this->read_pos = $this->max_read_pos = $this->error_code = 0;
        $this->bin_str = '';
    }

    /**
     * 生成签名串
     * @param string $bin_str 二进制内容
     * @return string 
     */
    private function makeSignCode($bin_str)
    {
        return substr(md5($bin_str), 0, self::SIGN_CODE_LEN);
    }

    /**
     * 获取数据id
     * @return string|null
     */
    public function getPid()
    {
        if (!$this->unpack_head) {
            $this->unpackHead();
        }
        return $this->pid;
    }

    /**
     * 解包header区
     */
    private function unpackHead()
    {
        if ($this->unpack_head) {
            return;
        }
        $this->unpack_head = true;
        $total_len = $this->readLength();
        //长度错误
        if ($total_len !== $this->readAvailableLength()) {
            $this->error_code = self::ERROR_SIZE;
            return;
        }
        $this->pack_opt = $this->readUnsignedChar();
        //带pid
        if ($this->pack_opt & self::OPTION_PID) {
            $this->pid = $this->readString();
        }
    }

    /**
     * 数据解密
     * @param string $mask_key
     * @return bool
     */
    public function unmask($mask_key)
    {
        if (!$this->unpack_head) {
            $this->unpackHead();
        }
        //如果没有设置加密flag
        if (!($this->pack_opt & self::OPTION_MASK)) {
            $this->error_code = self::ERROR_MASK;
            return false;
        }
        $begin_pos = $this->getDataPos() + 1;
        $this->doMask($begin_pos, $mask_key);
        if (!$this->checkSignCode()) {
            $this->error_code = self::ERROR_MASK;
            return false;
        }
        $this->pack_opt ^= self::OPTION_MASK;
        return true;
    }

    /**
     * 解包二进制数据
     * @return bool|array
     */
    public function unpack()
    {
        if (!$this->unpack_head) {
            $this->unpackHead();
        }
        //如果还需要解密
        if ($this->pack_opt & self::OPTION_MASK) {
            $this->error_code = self::ERROR_MASK;
            return false;
        }
        if (($this->pack_opt & self::OPTION_SIGN) && !$this->checkSignCode()) {
            return false;
        }
        //协议字符串
        $protocol_str = $this->readString();
        $protocol = new BinaryBuffer($protocol_str);
        //先解析出协议
        $struct_list = $protocol->readProtocolStruct();
        //再解出数据
        $result = $this->readStructData($struct_list);
        return $result;
    }

    /**
     * 读出数据
     * @param array $struct_list
     * @return array
     */
    private function readStructData($struct_list)
    {
        $result = array();
        foreach ($struct_list as $name => $item) {
            if ($this->error_code > 0) {
                break;
            }
            $result[$name] = $this->readItemData($item, true);
        }
        return $result;
    }

    /**
     * 读出一项数据
     * @param array $item
     * @param int $is_property 是否是属性
     * @return mixed
     */
    private function readItemData($item, $is_property)
    {
        $item_type = $item['type'];
        switch ($item_type) {
            case 1: //string
            case 4: //binary
                $value = $this->readString();
                break;
            case 3: //float
                $value = $this->readFloat();
                break;
            case 8: //double
                $value = $this->readDouble();
                break;
            case 5: //list
                $length = $this->readLength();
                $value = array();
                if ($length > 0) {
                    $sub_item = $item['sub_item'];
                    for ($i = 0; $i < $length; ++$i) {
                        if ($this->error_code) {
                            break;
                        }
                        $value[] = $this->readItemData($sub_item, false);
                    }
                }
                break;
            case 7: //map
                $length = $this->readLength();
                $value = array();
                if ($length > 0) {
                    $key_item = $item['key_item'];
                    $value_item = $item['value_item'];
                    for ($i = 0; $i < $length; ++$i) {
                        if ($this->error_code) {
                            break;
                        }
                        $key = $this->readItemData($key_item, false);
                        $value[$key] = $this->readItemData($value_item, false);
                    }
                }
                break;
            case 6: //struct
                //如果是属性，要检查这个struct是否为null
                if ($is_property) {
                    $data_flag = $this->readUnsignedChar();
                    if (0xff !== $data_flag) {
                        $value = null;
                        break;
                    }
                }
                $sub_struct = $item['sub_struct'];
                $value = $this->readStructData($sub_struct);
                break;
            default:
                //如果是int
                if (isset(self::$read_int_func[$item_type])) {
                    $func_name = 'read'. self::$read_int_func[$item_type];
                    $value = call_user_func([$this, $func_name]);
                } else {
                    $value = null;
                    $this->error_code = self::ERROR_DATA;
                }
                break;
        }
        return $value;
    }

    /**
     * 读出协议结构
     * @return array
     */
    private function readProtocolStruct()
    {
        $result_arr = array();
        while (0 === $this->error_code && $this->read_pos < $this->max_read_pos) {
            $item_name = $this->readString();
            $item = $this->readProtocolItem();
            if ($this->error_code > 0) {
                break;
            }
            $result_arr[$item_name] = $item;
        }
        return $result_arr;
    }

    /**
     * 读出一个协议的item
     * @return array
     */
    private function readProtocolItem()
    {
        $result = array();
        $item_type = $this->readUnsignedChar();
        $result['type'] = $item_type;
        switch($item_type) {
            case 5: //list
                $result['sub_item'] = $this->readProtocolItem();
                break;
            case 7: //map
                $result['key_item'] = $this->readProtocolItem();
                $result['value_item'] = $this->readProtocolItem();
                break;
            case 6: //struct
                //子struct协议
                $sub_protocol = new BinaryBuffer($this->readString());
                $sub_struct = $sub_protocol->readProtocolStruct();
                $err_code = $sub_protocol->getErrorCode();
                if ( $err_code > 0) {
                    $this->error_code = $err_code;
                } else {
                    $result['sub_struct'] = $sub_struct;
                }
                break;
        }
        return $result;
    }

    /**
     * 验证数据签名
     * @return bool
     */
    private function checkSignCode()
    {
        if (!($this->pack_opt & self::OPTION_SIGN)) {
            $this->error_code = self::ERROR_SIGN_CODE;
            return false;
        }
        //找出参与签名数据的起始位置
        $begin_pos = $this->getDataPos();
        //如果剩余数据不够签名串，表示数据出错了
        if ($this->readAvailableLength() < self::SIGN_CODE_LEN) {
            $this->error_code = self::ERROR_DATA;
            return false;
        }
        //找出参与签名的数据
        $end_pos = self::SIGN_CODE_LEN * -1;
        $sign_str = substr($this->bin_str, $begin_pos, $end_pos);
        $sign_code = $this->makeSignCode($sign_str);
        if ($sign_code !== substr($this->bin_str, $end_pos)) {
            $this->error_code = self::ERROR_SIGN_CODE;
            return false;
        }
        $this->max_read_pos -= self::SIGN_CODE_LEN;
        $this->pack_opt ^= self::OPTION_SIGN;
        return true;
    }

    /**
     * 获取数据开始的位置
     */
    private function getDataPos()
    {
        $tmp_read_pos = $this->read_pos;
        $this->read_pos = 0;
        $this->readLength();
        $result = $this->read_pos;
        $this->read_pos = $tmp_read_pos;
        return $result;
    }

    /**
     * 是否签名
     * @return bool
     */
    public function isSign()
    {
        return ($this->pack_opt & self::OPTION_SIGN) > 0;
    }

    /**
     * 是否加密
     * @return bool
     */
    public function isMask()
    {
        return ($this->pack_opt & self::OPTION_MASK) > 0;
    }

    /**
     * 获取错误代码
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * 获取错误的描述内容
     * @return string
     */
    public function getErrorMessage()
    {
        static $msg_arr = array(
            0 => 'success',
            self::ERROR_SIZE => '数据长度出错',
            self::ERROR_SIGN_CODE => '数据签名出错',
            self::ERROR_DATA => '数据出错',
            self::ERROR_MASK => '数据解码出错'
        );
        $err_code = $this->error_code;
        return isset($msg_arr[$err_code]) ? $msg_arr[$err_code] : 'Unknown error';
    }
}
