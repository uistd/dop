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
     * 重写 加载一个模板，并将内容写入FileBuf
     * @param FileBuf $file_buf
     * @param string $tpl_name
     * @param null $data
     */
    public function loadTpl(FileBuf $file_buf, $tpl_name, $data = null)
    {
        $build_define_code = $this->getConfigBool('define_code', false);
        if ($build_define_code) {
            $file_buf->pushStr('define(function (require, exports, module) {');
            $file_buf->indentIncrease();
        }
        parent::loadTpl($file_buf, $tpl_name, $data);
        if ($build_define_code) {
            $file_buf->indentDecrease();
            $file_buf->pushStr('});');
        }
    }

    /**
     * 生成通用代码
     */
    public function buildCommonCode()
    {
        $folder = $this->getFolder();
        $dop_file = $folder->touch('', 'dop.js');
        $this->loadTpl($dop_file, 'tpl/dop.js');
    }

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
        $class_file->setVariableValue('struct_note', $struct->getNote());
        $import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        $init_buf = $class_file->getBuf('init_property');
        $name_space = $struct->getNamespace();
        if (!$method_buf || !$property_buf || !$import_buf || !$init_buf ) {
            throw new Exception('Tpl error, METHOD_BUF or PROPERTY_BUF or IMPORT_BUF or init_property not found!');
        }
        $import_buf->pushUniqueStr('var DopBase = require("' . $this->relativePath('/', $name_space) . 'dop");');
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
     * @param string $base_path dop基础目录
     * @param CodeBuf $use_buf
     */
    private function makeImportCode($item, $base_path, $use_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $class_name = $struct->getClassName();
            //$path = './'. $dop_base_path . $struct->getNamespace() . '/'. $class_name;
            $path = self::relativePath($struct->getNamespace(), $base_path). $class_name;
            $use_buf->pushUniqueStr('var '. $class_name .' = require("'.$path.'");');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $base_path, $use_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $base_path, $use_buf);
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
            case ItemType::DOUBLE:
                $str = 'number';
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
