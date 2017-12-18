<?php

namespace UiStd\Dop\Coder\Objc;

use UiStd\Dop\Build\CodeBuf;
use UiStd\Dop\Build\CoderBase;
use UiStd\Dop\Build\FileBuf;
use UiStd\Dop\Build\StrBuf;
use UiStd\Dop\Exception;
use UiStd\Dop\Protocol\Item;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;

/**
 * Class HeadCoder
 * @package UiStd\Dop\Coder\Ojbc
 */
class HeadCoder extends CoderBase
{
    /**
     * @var Coder
     */
    protected $parent;

    /**
     * 返回 property 类型
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
        $this->readClassConfig($head_file, $struct);
        $head_import_buf = $head_file->getBuf(FileBuf::IMPORT_BUF);
        $head_property_buf = $head_file->getBuf(FileBuf::PROPERTY_BUF);
        $head_file->setVariableValue('class_name', $this->parent->makeClassName($struct));
        $implement_buf = new StrBuf();
        $this->fixClassName($implement_buf, $head_file);
        $head_file->setVariableValue('implement', $implement_buf->dump());
        $parent_class = 'NSObject';
        $extend_class = $struct->getParent();
        if (null !== $extend_class) {
            /** @var Coder $current_coder */
            $current_coder = $this->manager->getCurrentCoder();
            $parent_class = $current_coder->makeClassName($extend_class);
            $head_import_buf->pushStr('#import "' . $parent_class . '.h"');
        }
        $head_file->setVariableValue('parent', $parent_class);
        $item_list = $struct->getAllItem();
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
            $property_line_buf->pushStr('@property (nonatomic, ' . $this->propertyType($item->getType()) . ') ' . $this->parent->varType($item) . ' ' . $name . ';');
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
            $class_name = $this->parent->makeClassName($struct);
            $import_buf->pushUniqueStr('#import "' . $class_name . '.h"');
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeHeaderImportCode($item->getItem(), $import_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeHeaderImportCode($item->getValueItem(), $import_buf);
        }
    }

    /**
     * @param StrBuf $class_name_buf
     * @param FileBuf $file_buf
     */
    public function fixClassName($class_name_buf, $file_buf)
    {
        $import = $file_buf->getBuf(FileBuf::IMPORT_BUF);
        /** @var StrBuf $implement_buf */
        $implement_buf = $file_buf->getBuf(FileBuf::IMPLEMENT_BUF);
        if ($implement_buf && !$implement_buf->isEmpty()) {
            $import_class = $implement_buf->dump();
            if ($import) {
                $import->pushStr('#import "' . $import_class . '.h"');
            }
            $class_name_buf->pushStr(' <' . $import_class . '>');
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
        $class_name = $this->parent->makeClassName($struct);
        $file_name = $class_name . '.h';
        $class_name = $folder->getFile($path, $file_name);
        if (null === $class_name) {
            $class_name = $folder->touch($path, $file_name);
        }
        return $class_name;
    }

    /**
     * 获取coder
     * @param string $pack_type
     * @return \UiStd\Dop\Build\PackerBase
     */
    public function getPackInstance($pack_type)
    {
        $pack_type = 'head_' . $pack_type;
        return parent::getPackInstance($pack_type);
    }
}
