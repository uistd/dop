<?php
//一些通用的方法
/**
 * 生成临时变量名
 * @param string $var
 * @param string $type
 * @return string
 */
function tmp_var_name($var, $type)
{
    return $type . '_' . (string)$var;
}