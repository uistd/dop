<?php

namespace ffan\dop\coder\js;
use ffan\dop\build\CodeBuf;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\MapItem;
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
        $build_define_code = $this->getConfigBool('define_code', false);
        if ($build_define_code) {
            $class_file->pushStr('define(function (require, exports, module) {');
            $class_file->indentIncrease();
        }
        $this->loadTpl($class_file, 'tpl/class.tpl');
        $class_file->setVariableValue('class_name', $class_name);
        $dop_base_path = $this->getConfigString('require_path', 'dop');
        $class_file->setVariableValue('dop_base_path', $dop_base_path);
        $class_file->setVariableValue('struct_note', $struct->getNote());
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        $init_buf = $class_file->getBuf('init_property');
        if (!$method_buf || !$property_buf || !$use_buf || !$init_buf ) {
            throw new Exception('Tpl error, METHOD_BUF or PROPERTY_BUF or IMPORT_BUF or init_property not found!');
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
            $this->makeImportCode($item, $dop_base_path, $use_buf);
            $this->makeInitCode($name, $item, $init_buf);
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
        //写入dopClassName，保证js语法正确
        if (!$method_buf->isEmpty() || !$property_buf->isEmpty()) {
            $method_buf->pushStr('dopClassName: "'. $class_name .'"');
        }
        if ($build_define_code) {
            $class_file->indentDecrease();
            $class_file->pushStr('});');
        }
    }

    /**
     * 生成初始化属性的代码
     * 因为javascript prototype属性对[], {}是引用使用，所以在function 里初始化
     * @param string $name
     * @param Item $item
     * @param CodeBuf $init_buf
     */
    private function makeInitCode($name, $item, $init_buf)
    {
        $type = $item->getType();
        if (ItemType::ARR === $type) {
            $init_buf->pushStr('this.'. $name. ' = [];');
        } elseif (ItemType::MAP === $type) {
            $init_buf->pushStr('this.'. $name. ' = {};');
        } elseif (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $init_buf->pushStr('this.'. $name. ' = new '. $struct->getClassName() .'();');
        }
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param string $dop_base_path dop基础目录
     * @param CodeBuf $use_buf
     */
    private function makeImportCode($item, $dop_base_path, $use_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $class_name = $struct->getClassName();
            $path = './'. $dop_base_path . $struct->getNamespace() . '/'. $class_name;
            $use_buf->pushLockStr('var '. $class_name .' = require("'.$path.'");');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $dop_base_path, $use_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $dop_base_path, $use_buf);
        }
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
