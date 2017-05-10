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
}
