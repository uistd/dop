<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\IntItem;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * Class Coder
 * @package ffan\dop\coder\objc
 */
class Coder extends CoderBase
{
    /**
     * 变量类型
     * @param Item $item
     * @param int $depth 深度
     * @param bool $is_object 是否必须是对象  例如： int 和 Integer 的区别
     * @return string
     */
    public static function varType(Item $item, $depth = 0, $is_object = false)
    {
        $type = $item->getType();
        $str = '';
        switch ($type) {
            case ItemType::BINARY:
                $str = 'NSData*';
                break;
            case ItemType::STRING:
                $str = 'NSString*';
                break;
            case ItemType::FLOAT:
                $str = 'float';
                break;
            case ItemType::DOUBLE:
                $str = 'double';
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $item->getStructName() .'*';
                break;
            case ItemType::MAP;
                $str = 'NSMutableDictionary*';
                break;
            case ItemType::ARR:
                $str = 'NSArray*';
                break;
            case ItemType::INT:
                return 'int';
                break;
            case ItemType::BOOL:
                $str = 'BOOL';
                break;
        }
        return $str;
    }

    /**
     * 返回@property 类型
     * @param int $type
     * @return string
     */
    private function propertyType($type)
    {
        $str = 'retain';
        switch ($type)
        {
            case ItemType::INT:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
            case ItemType::BOOL:
                $str = 'assign';
                break;
            case ItemType::STRING:
                $str = 'copy';
                break;
        }
        return $str;
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     * @throws Exception
     */
    public function codeByStruct($struct)
    {
        $main_class_name = $struct->getClassName($struct);
        $head_file = $this->getClassFileBuf($struct, 'h');
        $class_file = $this->getClassFileBuf($struct, 'm');
        $this->loadTpl($head_file, 'tpl/header.h');
        $this->loadTpl($class_file, 'tpl/class.m');
        $head_file->pushToBuf('class_name', $main_class_name);
        $class_file->pushToBuf('class_name', $main_class_name);

        $class_import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $class_method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $class_property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);

        $head_import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $head_method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $head_property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);


        $item_list = $struct->getAllExtendItem();
        $is_first_property = true;

        $name_space = $struct->getNamespace();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            if (!$is_first_property) {
                $class_property_buf->emptyLine();
            } else {
                $is_first_property = false;
            }
            $this->makeClassImportCode($item, $name_space, $class_import_buf);
            $this->makeHeaderImportCode($item, $head_import_buf);
            $item_type = self::varType($item);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $class_property_buf->pushStr('/**');
                $class_property_buf->pushStr(' * ' . $tmp_node);
                $class_property_buf->pushStr(' */');
            }
            $property_line_buf = new StrBuf();
            $class_property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr('@property (nonatomic, ' . $this->propertyType($item_type) . ') ' . self::varType($item) .' ' . $name);
            if ($item->hasDefault()) {
                $property_line_buf->pushStr(' = ' . $item->getDefault());
            }
            $property_line_buf->pushStr(';');
        }
        //$this->packMethodCode($class_file, $struct);
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param string $base_path dop基础目录
     * @param CodeBuf $import_buf
     */
    private function makeClassImportCode($item, $base_path, $import_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $class_name = $this->makeClassName($struct);
            $path = self::relativePath($struct->getNamespace(), $base_path). $class_name;
            $import_buf->pushUniqueStr('#import "'.$path . $class_name .'"');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $base_path, $import_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $base_path, $import_buf);
        }
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param CodeBuf $import_buf
     */
    private function makeHeaderImportCode($item, $import_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $class_name = $this->makeClassName($struct);
            $import_buf->pushUniqueStr('@class '.$class_name .'"');
        }
    }

    /**
     * 通用文件
     */
    public function buildCommonCode()
    {

    }

    /**
     * 路径转全名空间
     * @param string $path
     * @return mixed
     */
    private function pathToNs($path)
    {
        return str_replace('/', '.', $path);
    }

    /**
     * 连接命名空间
     * @param string $ns
     * @param string $class_name
     * @param string $separator
     * @return string
     */
    public function joinNameSpace($ns, $class_name = '', $separator = '.')
    {
        $ns = trim($this->pathToNs($ns), ' .');
        $result = $this->getConfig('package', 'com.ffan.dop');
        if (!empty($ns)) {
            $result .= '.'. $ns;
        }
        if ($class_name) {
            $result .= '.' . $class_name;
        }
        return $result .';';
    }

    /**
     * 获取php class的fileBuf
     * @param Struct $struct
     * @param string $extend 后缀
     * @return FileBuf
     */
    public function getClassFileBuf($struct, $extend = 'h')
    {
        $folder = $this->getFolder();
        $path = $struct->getNamespace();
        $file = $struct->getFile();
        $file_name = ucfirst($file) . $struct->getClassName() . '.'. $extend;
        $file = $folder->getFile($path, $file_name);
        if (null === $file) {
            $file = $folder->touch($path, $file_name);
        }
        return $file;
    }

    /**
     * 生成类名
     * @param Struct $struct
     * @return string
     */
    public function makeClassName($struct)
    {
        return ucfirst($struct->getFile()) . $struct->getClassName();
    }

    /**
     * 获取map 迭代器的类型
     * @param Item $item
     * @return string
     */
    public function getMapIteratorType(Item $item) {
        $for_type = substr(self::varType($item), 3);
        return 'Map.Entry'. $for_type;
    }
}
