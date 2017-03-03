<?php
namespace ffan\dop;

/**
 * Class ProtocolManager
 * @package ffan\dop
 */
class ProtocolManager
{
    /**
     * @var array 所有的struct列表
     */
    private static $struct_list = [];

    /**
     * @var array 解析过的xml列表
     */
    private static $xml_list = [];

    /**
     * 添加一个Struct对象
     * @param Struct $struct
     * @throws DOPException
     */
    public static function addStruct(Struct $struct)
    {
        $namespace = $struct->getNamespace();
        $class_name = $struct->getClassName();
        if (!isset(self::$struct_list[$namespace])) {
            self::$struct_list[$namespace] = array();
        }
        if (isset(self::$struct_list[$namespace][$class_name])) {
            throw new DOPException('struct:' . $namespace . '/' . $class_name . ' conflict');
        }
        self::$struct_list[$namespace][$class_name] = $struct;
    }

    /**
     * 加载某个struct
     * @param string $namespace 命名空间
     * @param string $className 类名
     * @return Struct
     */
    public static function loadStruct($namespace, $className)
    {

    }

    /**
     * 是否存在某个Struct
     * @param $namespace
     * @param $className
     * @return bool
     */
    public static function hasStruct($namespace, $className)
    {
        return isset(self::$struct_list[$namespace][$className]);
    }

    /**
     * 把已经解析过的xml文件加入已经解析列表
     * @param string $file_name
     */
    public static function pushXmlFile($file_name)
    {
        self::$xml_list[$file_name] = true;
    }
}
