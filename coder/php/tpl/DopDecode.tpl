<?php

namespace ffan\dop;

/**
 * Class DopDecode PHP二进制解包类
 * @package ffan\dop
 */
class DopDecode
{
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
    const LITTLE_ENDIAN = 0;

    /**
     * 二进制字符串
     * @var string
     */
    private $bin_str = '';
    
    /**
     * @var int 字节序
     */
    private $endian;

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
     * @var bool 是否已经解析过head了
     */
    private $is_unpack_head = false;
    
    /**
     * BinaryBuffer constructor.
     * @param null|string $raw_data 初始数据
     */
    public function __construct($raw_data)
    {
        if (!is_string($raw_data)) {
            $this->error_code = self::ERROR_DATA;
            return;
        }
        $len = strlen($raw_data);
        if (0 === $len) {
            $this->error_code = self::ERROR_DATA;
            return;
        }
        $this->max_read_pos = $len;
        $this->bin_str = $raw_data;
    }
    
    /**
     * 读出一个长度值
     * @return int
     */
    private function readLength()
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
            return $this->readBigInt();
        }
    }
    
    /**
     * 读出一个char
     * @return int
     */
    private function readChar()
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
    private function readUnsignedChar()
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
    private function readShort()
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
    private function readUnsignedShort()
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
     * 读出一个32位有符号 int
     * @return int
     */
    private function readInt()
    {
        $result = $this->readUnsignedInt();
        if ($result > 0x7fffffff) {
            $result = (0xffffffff - $result + 1) * -1;
        }
        return $result;
    }

    /**
     * 读出一个32位有符号 int
     * @return int
     */
    private function readUnsignedInt()
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
     * 读出一个64位有符号 int
     * @return int
     */
    private function readBigInt()
    {
        if (!$this->sizeCheck(8)) {
            return 0;
        }
        $pack_arg = $this->endian === self::LITTLE_ENDIAN ? 'P' : 'J';
        $sub_str = substr($this->bin_str, $this->read_pos, 8);
        $this->read_pos += 8;
        $result = unpack($pack_arg . 're', $sub_str);
        $value = $result['re'];
        if ($value > 0x7fffffffffffffff) {
            $value = (0xffffffffffffffff - $value + 1) * -1;
        }
        return $value;
    }

    /**
     * 读出一个符点数
     * @return float
     */
    private function readFloat()
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
    private function readDouble()
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
    private function readString()
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
     * 获取可读长度
     * @return int
     */
    private function readAvailableLength()
    {
        $result = $this->max_read_pos - $this->read_pos;
        return $result > 0 ? $result : 0;
    }

    /**
     * 获取数据id
     * @return string|null
     */
    public function getPid()
    {
        if (!$this->is_unpack_head) {
            $this->unpackHead();
        }
        return $this->pid;
    }

    /**
     * 解包header区
     */
    private function unpackHead()
    {
        $this->is_unpack_head = true;
        $this->pack_opt = $this->readUnsignedChar();
        //字节序判断
        if ($this->pack_opt & DopEncode::OPTION_ENDIAN) {
            $this->endian = self::BIG_ENDIAN;
        } else {
            $this->endian = self::LITTLE_ENDIAN;
        }
        $total_len = $this->readLength();
        //长度错误
        if ($total_len !== $this->readAvailableLength()) {
            $this->error_code = self::ERROR_SIZE;
            return;
        }
        //重置this->bin_str，不要前面的length和option标志位
        $this->bin_str = substr($this->bin_str, $this->read_pos);
        $this->max_read_pos -= $this->read_pos;
        $this->read_pos = 0;
        //带pid
        if ($this->pack_opt & DopEncode::OPTION_PID) {
            $this->pid = $this->readString();
        }
    }

    /**
     * 解包二进制数据
     * @return bool|array
     */
    public function unpack()
    {
        if (!$this->is_unpack_head) {
            $this->unpackHead();
        }
        //如果还需要解密
        if ($this->pack_opt & DopEncode::OPTION_MASK) {
            $this->error_code = self::ERROR_MASK;
            return false;
        }
        if (($this->pack_opt & DopEncode::OPTION_SIGN) && !$this->checkSignCode()) {
            return false;
        }
        //协议字符串
        $protocol_str = $this->readString();
        $protocol = new DopDecode($protocol_str);
        //先解析出协议
        $struct_list = $protocol->readProtocolStruct();
        //再解出数据
        $result = $this->readStructData($struct_list);
        return $result;
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
                $value = $this->tryReadInt($item_type);
                break;
        }
        return $value;
    }

    /**
     * 尝试读int
     * @param int $item_type
     * @return int|null
     */
    private function tryReadInt($item_type)
    {
        /*
        0x12 => 'Char',
        0x92 => 'UnsignedChar',
        0x22 => 'Short',
        0xa2 => 'UnsignedShort',
        0x42 => 'Int',
        0xc2 => 'UnsignedInt',
        0x82 => 'Bigint'
        */
        switch ($item_type) {
            case 0x12:
                $value = $this->readChar();
                break;
            case 0x92:
                $value = $this->readUnsignedChar();
                break;
            case 0x22:
                $value = $this->readShort();
                break;
            case 0xa2:
                $value = $this->readUnsignedShort();
                break;
            case 0x42:
                $value = $this->readInt();
                break;
            case 0xc2:
                $value = $this->readUnsignedInt();
                break;
            case 0x82:
                $value = $this->readBigInt();
                break;
            default:
                $value = null;
                $this->error_code = self::ERROR_DATA;
        }
        return $value;
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
        switch ($item_type) {
            case 5: //list
                $result['sub_item'] = $this->readProtocolItem();
                break;
            case 7: //map
                $result['key_item'] = $this->readProtocolItem();
                $result['value_item'] = $this->readProtocolItem();
                break;
            case 6: //struct
                //子struct协议
                $sub_protocol = new DopDecode($this->readString());
                $sub_struct = $sub_protocol->readProtocolStruct();
                $err_code = $sub_protocol->getErrorCode();
                if ($err_code > 0) {
                    $this->error_code = $err_code;
                } else {
                    $result['sub_struct'] = $sub_struct;
                }
                break;
        }
        return $result;
    }

    /**
     * 数据加密
     * @param string $mask_key
     * @return bool
     */
    public function unMask($mask_key)
    {
        if (!$this->is_unpack_head) {
            $this->unpackHead();
        }
        //数据未被加密
        if (!($this->pack_opt & DopEncode::OPTION_MASK)) {
            return true;
        }
        $mask_key = DopEncode::fixMaskKey($mask_key);
        $mask_len = strlen($mask_key);
        $pos = 0;
        for ($i = $this->read_pos; $i < $this->max_read_pos; ++$i) {
            $index = $pos++ % $mask_len;
            $this->bin_str{$i} = $this->bin_str{$i} ^ $mask_key{$index};
        }
        //解密后，判断签名串
        if (!$this->checkSignCode()) {
            $this->error_code = self::ERROR_MASK;
            return false;
        }
        $this->pack_opt ^= DopEncode::OPTION_MASK;
        return true;
    }

    /**
     * 验证数据签名
     * @return bool
     */
    private function checkSignCode()
    {
        //如果剩余数据不够签名串，表示数据出错了
        if ($this->readAvailableLength() < DopEncode::SIGN_CODE_LEN) {
            $this->error_code = self::ERROR_DATA;
            return false;
        }
        //找出参与签名的数据
        $end_pos = DopEncode::SIGN_CODE_LEN * -1;
        $sign_str = substr($this->bin_str, 0, $end_pos);
        $sign_code = DopEncode::makeSignCode($sign_str);
        if ($sign_code !== substr($this->bin_str, $end_pos)) {
            $this->error_code = self::ERROR_SIGN_CODE;
            return false;
        }
        $this->max_read_pos -= DopEncode::SIGN_CODE_LEN;
        $this->pack_opt ^= DopEncode::OPTION_SIGN;
        return true;
    }

    /**
     * 是否加密
     * @return bool
     */
    public function isMask()
    {
        if (!$this->is_unpack_head) {
            $this->unpackHead();
        }
        return ($this->pack_opt & DopEncode::OPTION_MASK) > 0;
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
