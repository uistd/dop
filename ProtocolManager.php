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
        $full_name = $namespace .'/'. $class_name;
        if (isset(self::$struct_list[$full_name])) {
            throw new DOPException('struct:' . $full_name . ' conflict');
        }
        self::$struct_list[$full_name] = $struct;
    }

    /**
     * 加载某个struct
     * @param string $fullName
     * @return Struct
     */
    public static function loadStruct($fullName)
    {

    }

    /**
     * 是否存在某个Struct
     * @param string $fullName
     * @return bool
     */
    public static function hasStruct($fullName)
    {
        return isset(self::$struct_list[$fullName]);
    }

    /**
     * 把已经解析过的xml文件加入已经解析列表
     * @param string $file_name
     */
    public static function pushXmlFile($file_name)
    {
        self::$xml_list[$file_name] = true;
    }

    /**
     * 获取所有的struct
     * @return array[Struct]
     */
    public static function getAll()
    {
        return self::$struct_list;
    }
}
