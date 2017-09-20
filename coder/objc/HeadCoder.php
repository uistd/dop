<?php

namespace FFan\Dop\Coder\Objc;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\CoderBase;
use FFan\Dop\Build\FileBuf;
use FFan\Dop\Build\StrBuf;
use FFan\Dop\Exception;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;

/**
 * Class HeadCoder
 * @package FFan\Dop\Coder\Ojbc
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

        $head_import_buf = $head_file->getBuf(FileBuf::IMPORT_BUF);
        $head_property_buf = $head_file->getBuf(FileBuf::PROPERTY_BUF);
        $head_file->setVariableValue('class_name', $this->parent->makeClassName($struct));
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
            $property_line_buf->pushStr('@property (nonatomic, ' . $this->propertyType($item->getType()) . ') ' . $this->parent->varType($item) . ' ' . $name .';');
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
     * @return \FFan\Dop\Build\PackerBase
     */
    public function getPackInstance($pack_type)
    {
        $pack_type = 'head' . ucfirst($pack_type);
        return parent::getPackInstance($pack_type);
    }
}
