<?php

namespace UiStd\Dop\Coder\Java;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\CoderBase;
use UiStd\Dop\Build\FileBuf;
use UiStd\Dop\Build\StrBuf;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\IntItem;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;
use UiStd\Common\Str as UisStr;

/**
 * Class Coder
 * @package UiStd\Dop\Coder\Java
 */
class Coder extends CoderBase
{
    /**
     * 变量类型
     * @param Item $item
     * @param int $depth 深度
     * @param bool $is_interface 是否返回接口名，例如：List 和 ArrayList的区别
     * @param bool $is_object 是否必须是对象  例如： int 和 Integer 的区别
     * @return string
     */
    public static function varType(Item $item, $depth = 0, $is_interface = true, $is_object = false)
    {
        $type = $item->getType();
        $str = '';
        switch ($type) {
            case ItemType::BINARY:
                $str = 'byte[]';
                break;
            case ItemType::STRING:
                $str = 'String';
                break;
            case ItemType::FLOAT:
                $str = $is_object ? 'Float' : 'float';
                break;
            case ItemType::DOUBLE:
                $str = $is_object ? 'Double' : 'double';
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $item->getStructName();
                break;
            case ItemType::MAP;
                $str = $is_interface ? 'Map' : 'HashMap';
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $key_type = self::varType($key_item, $depth + 1, false, true);
                $value_type = self::varType($value_item, $depth + 1, false, true);
                $str .= '<' . $key_type . ', ' . $value_type . '>';
                break;
            case ItemType::ARR:
                $str = $is_interface ? 'List' : 'ArrayList';
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $sub_type = self::varType($sub_item, $depth + 1, false, true);
                $str .= '<' . $sub_type . '>';
                break;
            case ItemType::INT:
                /** @var IntItem $item */
                $byte = $item->getByte();
                //因为java不支持unsigned 所以 升位 表示。 char 升 short, short升int int升long
                if ($item->isUnsigned()) {
                    $byte <<= 1;
                }
                if (IntItem::BYTE_TINY === $byte) {
                    $str = $is_object ? 'Byte' : 'byte';
                } elseif (IntItem::BYTE_SMALL === $byte) {
                    $str = $is_object ? 'Short' : 'short';
                } elseif (IntItem::BYTE_INT === $byte) {
                    $str = $is_object ? 'Integer' : 'int';
                } else {
                    $str = $is_object ? 'Long' : 'long';
                }
                break;
            case ItemType::BOOL:
                $str = 'Boolean';
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
        $main_class_name = $struct->getClassName();
        $name_space = $struct->getNamespace();
        $class_file = $this->getClassFileBuf($struct);
        $this->loadTpl($class_file, 'tpl/class.java');
        $class_name_buf = $class_file->getBuf('java_class');
        if (null === $class_name_buf) {
            throw new Exception('Can not found class name buf');
        }
        $class_name_buf->pushStr($main_class_name);
        $import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        if (!$method_buf || !$property_buf || !$import_buf) {
            throw new Exception('Tpl error, METHOD_BUF or PROPERTY_BUF or IMPORT_BUF not found!');
        }
        $this->readClassConfig($class_file, $struct);
        //模板中的变量处理
        $class_file->setVariableValue('package', $this->joinNameSpace($name_space));
        $class_file->setVariableValue('struct_node', ' ' . $struct->getNote());
        $item_list = $struct->getAllExtendItem();
        $is_first_property = true;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            if (!$is_first_property) {
                $property_buf->emptyLine();
            } else {
                $is_first_property = false;
            }
            $this->makeImportCode($item, $name_space, $import_buf);
            $item_type = self::varType($item);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $property_buf->pushStr('/**');
                $property_buf->pushStr(' * ' . $tmp_node);
                $property_buf->pushStr(' */');
            }
            $property_line_buf = new StrBuf();
            $property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr($item_type . ' ' . $name);
            if ($item->hasDefault()) {
                $property_line_buf->pushStr(' = ' . $item->getDefault());
            }
            $property_line_buf->pushStr(';');

            $method_buf->emptyLine();
            $p_name = UisStr::camelName($name);
            $method_buf->pushStr('public '. $item_type .' get'.$p_name .' {');
            $method_buf->pushIndent('return this.'. $name.';');
            $method_buf->pushStr('}');
            $method_buf->emptyLine();
            $method_buf->pushStr('public void set'. $p_name .'('.$item_type.' '.$name.') {');
            $method_buf->pushIndent('this.'. $name .' = '. $name. ';');
            $method_buf->pushStr('}');

        }
        $this->packMethodCode($class_file, $struct);
        $this->fixClassName($class_name_buf, $class_file);
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param string $name_space 所在命名空间
     * @param CodeBuf $import_buf
     */
    private function makeImportCode($item, $name_space, $import_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $package = $struct->getNamespace();
            if ($package !== $name_space) {
                $use_name_space = $this->joinNameSpace($package, $struct->getClassName());
                $import_buf->pushUniqueStr('import ' . $use_name_space . ';');
            }
        } elseif (ItemType::ARR === $type) {
            $type_str = self::varType($item);
            $import_buf->pushUniqueStr('import java.util.List;');
            if (false !== strpos($type_str, 'ArrayList')) {
                $import_buf->pushUniqueStr('import java.util.ArrayList;');
            }
            $import_buf->pushUniqueStr('import java.util.List;');
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $name_space, $import_buf);
        } elseif (ItemType::MAP === $type) {
            $import_buf->pushUniqueStr('import java.util.Map;');
            $type_str = self::varType($item);
            if (false !== strpos($type_str, 'HashMap')) {
                $import_buf->pushUniqueStr('import java.util.HashMap;');
            }
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $name_space, $import_buf);
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
        $result = $this->getConfig('package', 'com.uis.dop');
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
     * @return FileBuf
     */
    public function getClassFileBuf($struct)
    {
        $folder = $this->getFolder();
        $path = $struct->getNamespace();
        $file_name = $struct->getClassName() . '.java';
        $file = $folder->getFile($path, $file_name);
        if (null === $file) {
            $file = $folder->touch($path, $file_name);
        }
        return $file;
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
