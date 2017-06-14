<?php

namespace ffan\dop\coder\js;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;

/**
 * Class Coder
 * @package ffan\dop\coder\php
 */
class Coder extends CoderBase
{
    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     * @throws Exception
     */
    public function codeByStruct($struct)
    {
        $class_name = $struct->getClassName();
        $class_file = $this->getClassFileBuf($struct);
        $this->loadTpl($class_file, 'tpl/class.tpl');
        $class_file->setVariableValue('class_name', $class_name);
        $class_file->setVariableValue('dop_base_path', $this->getConfigString('require_path', 'dop'));
        $class_file->setVariableValue('struct_note', $struct->getNote());
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        if (!$use_buf || !$property_buf ) {
            throw new Exception('Tpl error, IMPORT_BUF or PROPERTY_BUF not found!');
        }
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
            $property_buf->pushStr('/**');
            $item_type = self::varType($item);
            $property_desc_buf = new StrBuf();
            $property_buf->insertBuf($property_desc_buf);
            $property_buf->pushStr(' * @var {' . $item_type.'}');
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $property_desc_buf->pushStr(' ' . $tmp_node);
            }
            $property_buf->pushStr(' */');
            $property_line_buf = new StrBuf();
            $property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr($name.': ');
            if ($item->hasDefault()) {
                $property_line_buf->pushStr($item->getDefault());
            } else {
                $property_line_buf->pushStr(self::defaultValue($item));
            }
            $property_line_buf->pushStr(',');
        }
        $this->packMethodCode($class_file, $struct);
    }

    /**
     * 变量类型
     * @param Item $item
     * @return string
     */
    public static function varType(Item $item)
    {
        $type = $item->getType();
        $str = '*';
        switch ($type) {
            case ItemType::BINARY:
            case ItemType::STRING:
                $str = 'string';
                break;
            case ItemType::FLOAT:
                $str = 'float';
                break;
            case ItemType::DOUBLE:
                $str = 'double';
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $item->getStructName();
                break;
            case ItemType::MAP;
                $str = 'Object';
                break;
            case ItemType::ARR:
                $str = 'Array';
                break;
            case ItemType::INT:
                $str = 'int';
                break;
        }
        return $str;
    }

    /**
     * 默认值
     * @param Item $item
     * @return string
     */
    public static function defaultValue(Item $item)
    {
        $type = $item->getType();
        $str = 'null';
        switch ($type) {
            case ItemType::BINARY:
            case ItemType::STRING:
                $str = '""';
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $str = '0.0';
                break;
            case ItemType::INT:
                $str = '0';
                break;
        }
        return $str;
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
        $file_name = $struct->getClassName() .'.js';
        $file = $folder->getFile($path, $file_name);
        if (null === $file) {
            $file = $folder->touch($path, $file_name);
        }
        return $file;
    }
}
