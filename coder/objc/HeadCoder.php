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
 * Class HeadCoder
 * @package ffan\dop\coder\objc
 */
class HeadCoder extends CoderBase
{
    /**
     * 变量类型
     * @param Item $item
     * @param bool $is_object 是否是对象
     * @return string
     */
    public static function varType(Item $item, $is_object = false)
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
                $str = $item->getStructName() . '*';
                break;
            case ItemType::MAP;
                /** @var MapItem $item */
                $key_item = $item->getKeyItem();
                $value_item = $item->getValueItem();
                $str = 'NSMutableDictionary <' . self::varType($key_item, true) . ', ' . self::varType($value_item, true) . '>*';
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $str = 'NSMutableArray <' . self::varType($sub_item, true) . '>*';
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
     * 返回@property 类型
     * @param int $type
     * @return string
     */
    private function propertyType($type)
    {
        $str = 'retain';
        switch ($type) {
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
        $head_file = $this->getClassFileBuf($struct);
        $this->loadTpl($head_file, 'tpl/header.h');

        $head_import_buf = $head_file->getBuf(FileBuf::IMPORT_BUF);
        $head_method_buf = $head_file->getBuf(FileBuf::METHOD_BUF);
        $head_property_buf = $head_file->getBuf(FileBuf::PROPERTY_BUF);

        $item_list = $struct->getAllExtendItem();
        $is_first_property = true;
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            if (!$is_first_property) {
                $head_property_buf->emptyLine();
            } else {
                $is_first_property = false;
            }
            $this->makeHeaderImportCode($item, $head_import_buf);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $head_property_buf->pushStr('/**');
                $head_property_buf->pushStr(' * ' . $tmp_node);
                $head_property_buf->pushStr(' */');
            }
            $property_line_buf = new StrBuf();
            $head_property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr('@property (nonatomic, ' . $this->propertyType($item->getType()) . ') ' . self::varType($item) . ' ' . $name);
            if ($item->hasDefault()) {
                $property_line_buf->pushStr(' = ' . $item->getDefault());
            }
            $property_line_buf->pushStr(';');
        }
        $this->packMethodCode($head_file, $struct);
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
            $class_name = self::makeClassName($struct);
            $import_buf->pushUniqueStr('@class ' . $class_name . '"');
        }
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
        $class_name = self::makeClassName($struct);
        $file_name = $class_name . '.h';
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
    public static function makeClassName($struct)
    {
        return ucfirst(basename($struct->getFile(), '.xml')) . $struct->getClassName();
    }

    /**
     * 获取coder
     * @param string $pack_type
     * @return \ffan\dop\build\PackerBase
     */
    public function getPackInstance($pack_type)
    {
        $pack_type = 'head' . ucfirst($pack_type);
        return parent::getPackInstance($pack_type);
    }
}
