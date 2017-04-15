<?php
define('DOP_PHP_PROTOCOL_BASE', __DIR__ . DIRECTORY_SEPARATOR);
/**
 * autoload 方法
 * @param string $full_name
 */
function dop_protocol_autoload($full_name)
{
    $ns_pos = strrpos($full_name, '\\');
    $ns = substr($full_name, 0, $ns_pos);
    $namespace_set = array(
        
    );
    
    if (!isset($namespace_set[$ns])) {
        return;
    }
    $base_path = DOP_PHP_PROTOCOL_BASE . $namespace_set[$ns] . DIRECTORY_SEPARATOR;
    $class_name = substr($full_name, $ns_pos);
    $file_name = $base_path . $class_name .'.php';
    if (!is_file($file_name)) {
        return;
    }
    /** @noinspection PhpIncludeInspection */
    require_once $file_name;
}
//注册加载处理函数
spl_autoload_register('dop_protocol_autoload');