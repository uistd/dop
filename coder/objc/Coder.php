<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
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
     * @param bool $is_object 是否是对象
     * @param bool $is_property
     * @return string
     */
    public function varType(Item $item, $is_object = false, $is_property = true)
    {
        $type = $item->getType();
        $str = '';
        switch ($type) {
            case ItemType::BINARY:
                $str = 'NSMutableData*';
                break;
            case ItemType::STRING:
                $str = 'NSString*';
                break;
            case ItemType::FLOAT:
                if ($is_object) {
                    $str = 'NSNumber*';
                } else {
                    $str = 'float';
                }
                break;
            case ItemType::DOUBLE:
                if ($is_object) {
                    $str = 'NSNumber*';
                } else {
                    $str = 'double';
                }
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $this->makeClassName($item->getStruct()) . '*';
                break;
            case ItemType::MAP;
                if ($is_property) {
                    /** @var MapItem $item */
                    $key_item = $item->getKeyItem();
                    $value_item = $item->getValueItem();
                    $str = 'NSMutableDictionary <' . $this->varType($key_item, true) . ', ' . $this->varType($value_item, true) . '>*';
                } else {
                    $str = 'NSMutableDictionary *';
                }
                break;
            case ItemType::ARR:
                if ($is_property) {
                    /** @var ListItem $item */
                    $sub_item = $item->getItem();
                    $str = 'NSMutableArray <' . $this->varType($sub_item, true) . '>*';
                } else {
                    $str = 'NSMutableArray *';
                }
                break;
            case ItemType::INT:
                if ($is_object) {
                    $str = 'NSNumber*';
                } else {
                    /** @var IntItem $item */
                    $byte = $item->getByte();
                    $length = $byte * 8;
                    $str = 'int' . $length;
                    if ($item->isUnsigned()) {
                        $str = 'u' . $str;
                    }
                    $str .= '_t';
                }
                break;
            case ItemType::BOOL:
                $str = 'BOOL';
                break;
        }
        return $str;
    }

    /**
     * 生成代码
     */
    public function build()
    {
        //先生成header代码
        $head_coder = new HeadCoder($this->getManager(), $this->getBuildOption(), $this);
        $head_coder->build();
        parent::build();
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     * @throws Exception
     */
    public function codeByStruct($struct)
    {
        $main_class_name = $this->makeClassName($struct);
        $class_file = $this->getClassFileBuf($struct, 'm');
        $this->loadTpl($class_file, 'tpl/class.m');
        $class_file->setVariableValue('class_name', $main_class_name);
        $class_import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $class_import_buf->pushUniqueStr('#import "'. $this->makeClassName($struct) .'.h"');
        $item_list = $struct->getAllExtendItem();

        $name_space = $struct->getNamespace();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            $this->makeClassImportCode($item, $name_space, $class_import_buf);
        }
        $this->packMethodCode($class_file, $struct);
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
            $import_buf->pushUniqueStr('#import "'. $class_name .'.h"');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeClassImportCode($item->getItem(), $base_path, $import_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeClassImportCode($item->getValueItem(), $base_path, $import_buf);
        }
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
        $class_name = $this->makeClassName($struct);
        $file_name = $class_name .'.'. $extend;
        $class_name = $folder->getFile($path, $file_name);
        if (null === $class_name) {
            $class_name = $folder->touch($path, $file_name);
        }
        return $class_name;
    }

    /**
     * 生成类名
     * @param Struct $struct
     * @return string
     */
    public function makeClassName($struct)
    {
        $class_prefix = $this->getConfigString('class_prefix');
        if (empty($class_prefix) || !preg_match('/^[a-zA-Z][a-zA-Z\d]*$/', $class_prefix)) {
            $class_prefix = 'APP';
        }
        $class_name = $class_prefix . ucfirst(basename($struct->getFile(), '.xml')) . $struct->getClassName();
        return $class_name;
    }

    /**
     * 获取需要的kindOfClass name
     * @param Item $item
     * @return string
     */
    public function nsClassName($item)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::INT:
            case ItemType::BOOL:
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $type_name = 'NSNumber';
                break;
            case ItemType::BINARY:
                $type_name = 'NSData';
                break;
            case ItemType::STRING:
                $type_name = 'NSString';
                break;
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $type_name = $this->makeClassName($item->getStruct());
                break;
            case ItemType::MAP:
                $type_name = 'NSDictionary';
                break;
            case ItemType::ARR:
                $type_name = 'NSArray';
                break;
            default:
                $type_name = 'NSNull';
        }
        return $type_name;
    }
}