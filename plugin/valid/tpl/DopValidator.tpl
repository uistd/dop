<?php

namespace {{$namespace}};

/**
 * Class DopValidator
 * @package {{$namespace}}
 */
class DopValidator
{
    /**
     * 字符串长度计算方式：按实际占用字节数
     */
    const STR_LEN_BY_BYTE = 1;

    /**
     * 字符串长度计算方式：按显示宽度 传统的 ascii 占1位，其它2位
     */
    const STR_LEN_BY_DISPLAY = 2;

    /**
     * 字符串长度计算方式: 按字数 英文字母和汉字都是1的长度，比较容易理解
     */
    const STR_LEN_BY_LETTER = 3;

    /**
     * 长度确认
     * @param string $str 数据
     * @param int $str_len_type 字符串长度计算方式，1：实际字节数 2：显示宽度 3：固定为1
     * @param int $min_len 最小长度
     * @param int $max_len 最大长度
     * @return bool
     */
    public static function checkStrLength($str, $str_len_type, $min_len = null, $max_len = null)
    {
        if (null === $min_len && null === $max_len) {
            return true;
        }
        if (self::STR_LEN_BY_LETTER === $str_len_type) {
            $str_len = mb_strlen($str, 'utf-8');
        } elseif (self::STR_LEN_BY_DISPLAY === $str_len_type) {
            $str_len = 0;
            $php_str_len = strlen($str);
            for ($i = 0; $i < $php_str_len;) {
                $ord_code = ord($str{$i});
                //0xxxxxxx
                if ($ord_code <= 0x7f) {
                    $i++;
                    $str_len++;
                }//110xxxxx 10xxxxxx
                elseif ($ord_code <= 0xdf) {
                    $i += 2;
                    $str_len += 2;
                } //1110xxxx 10xxxxxx 10xxxxxx 
                elseif ($ord_code <= 0xef) {
                    $i += 3;
                    $str_len += 2;
                }//11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
                elseif ($ord_code <= 0xf7) {
                    $i += 4;
                    $str_len += 2;
                } //111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                elseif ($ord_code <= 0xfb) {
                    $i += 5;
                    $str_len += 2;
                } //1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                elseif ($ord_code <= 0xfd) {
                    $i += 6;
                    $str_len += 2;
                }
            }
            //不能识别的utf8字符串
            if ($i !== $php_str_len) {
                return false;
            }
        } else {
            $str_len = strlen($str);
        }
        if (null !== $min_len && $str_len < $min_len) {
            return false;
        }
        if (null !== $min_len && $str_len > $max_len) {
            return false;
        }
        return true;
    }

    /**
     * 是否是电话号码
     * @param string $mobile 电话号码
     * @return bool
     */
    public static function isValidMobile($mobile)
    {
        return preg_match('/^(\+86)?1[34578]{1}\d{9}$/', $mobile) > 0;
    }
    
    /**
     * 是否是邮箱地址
     * @param string $email 邮箱
     * @return bool
     */
    public static function isValidEmail($email)
    {
        return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $email) > 0;
    }
    
    /**
     * 是否是url地址
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url)
    {
        return preg_match('/(http|https|ftp|file){1}(:\/\/)?([\da-z-\.]+)\.([a-z]{2,6})([\/\w \.-?&%-=]*)*\/?/', $url) > 0;
    }

    /**
     * 是否是IP地址
     * @param string $ip
     * @return bool
     */
    public static function isValidIp($ip)
    {
        return false !== ip2long($ip);
    }
    
    /**
     * 是否是邮编
     * @param string $code
     * @return bool
     */
    public static function isValidZipCode($code)
    {
        return preg_match('/^[1-9]\d{5}(?!\d)$/', $code) > 0;
    }

    /**
     * 验证车牌号是否可用
     * @param string $plateNumber
     * @return bool
     */
    public static function isValidPlateNumber($plateNumber)
    {
        //注意，新能源车是车牌 比 普通车牌 多一位
        return preg_match('/^[京,津,渝,沪,冀,晋,辽,吉,黑,苏,浙,皖,闽,赣,鲁,豫,鄂,湘,粤,琼,川,贵,云,陕,秦,甘,陇,青,台,蒙,桂,宁,新,藏,澳,军,海,航,警]{1}[A-Za-z][\s-]?[0-9a-zA-Z]{5,6}$/u', $plateNumber) > 0;
    }
    
    /**
     * 是否是日期时间
     * @param string $date_str
     * @return bool
     */
    public static function isValidDate($date_str)
    {
        return preg_match('/^\d{4}((-|\/)\d{1,2}){2}$/', $date_str) > 0;
    }

    /**
     * 是否是日期 时间格式
     * @param string $time_str
     * @return bool
     */
    public static function isValidDateTime($time_str)
    {
        return preg_match('/^\d{4}((-|\/)\d{1,2}){2} \d{1,2}:\d{1,2}:\d{1,2}$/', $time_str) > 0;
    }

    /**
     * 电话号码
     * @param string $phone_number
     * @return bool
     */
    public static function isValidPhone($phone_number)
    {
        return preg_match('/^\d{3}-\d{8}|\d{4}-\d{7}$/', $phone_number) > 0;
    }

    /**
     * 是否是身份证
     * @param string $id_card 身份证号
     * @return string
     */
    public static function isValidIdCard($id_card)
    {
        if (!is_string($id_card)) {
            return false;
        }
        $id_card = trim($id_card);
        // 只能是18位
        if (strlen($id_card) != 18) {
            return false;
        }
        // 取出本体码
        $id_card_base = substr($id_card, 0, 17);
        // 取出校验码
        $verify_code = $id_card{17};
        if ('x' === $verify_code) {
            $verify_code = 'X';
        }
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += $id_card_base{$i} * $factor[$i];
        }
        // 取模
        $mod = $total % 11;
        // 比较校验码
        return $verify_code === $verify_code_list[$mod];
    }
    
    /**
     * 验证价格
     * @param float $price 价格
     * @return bool
     */
    public static function isValidPrice($price)
    {
        return preg_match('/^-?[\d]+(\.[\d]{0,2})?$/', $price) > 0;
    }
}
