<?php

namespace ffan\dop\coder\objc;

use ffan\dop\build\CodeBuf;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\Exception;
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
     * 生成代码
     */
    public function build()
    {
        //先生成header代码
        $head_coder = new HeadCoder($this->getManager(), $this->getBuildOption());
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
        $main_class_name = HeadCoder::makeClassName($struct);
        $class_file = $this->getClassFileBuf($struct, 'm');
        $this->loadTpl($class_file, 'tpl/class.m');
        $class_file->setVariableValue('class_name', $main_class_name);
        $class_import_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $class_import_buf->pushUniqueStr('#import "'. HeadCoder::makeClassName($struct) .'.h"');
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
            $class_name = HeadCoder::makeClassName($struct);
            $path = self::relativePath($struct->getNamespace(), $base_path);
            $import_buf->pushUniqueStr('#import "'.$path . $class_name .'.h"');
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
        $class_name = HeadCoder::makeClassName($struct);
        $file_name = $class_name .'.'. $extend;
        $class_name = $folder->getFile($path, $file_name);
        if (null === $class_name) {
            $class_name = $folder->touch($path, $file_name);
        }
        return $class_name;
    }
}
