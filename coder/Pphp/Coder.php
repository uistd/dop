<?php

namespace FFan\Dop\Coder\Php;

use FFan\Dop\Build\CodeBuf;
use FFan\Dop\Build\CoderBase;
use FFan\Dop\Build\FileBuf;
use FFan\Dop\Build\StrBuf;
use FFan\Dop\Exception;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\StructItem;
use FFan\Std\Common\Str;

/**
 * Class Coder
 * @package FFan\Dop\Coder\Php
 */
class Coder extends CoderBase
{
    /**
     * 生成后的主文件
     */
    const MAIN_FILE = 'dop.php';

    /**
     * 变量类型
     * @param Item $item
     * @return string
     */
    public static function varType(Item $item)
    {
        $type = $item->getType();
        $str = 'mixed';
        switch ($type) {
            case ItemType::BINARY:
            case ItemType::STRING:
                $str = 'string';
                break;
            case ItemType::FLOAT:
            case ItemType::DOUBLE:
                $str = 'float';
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $item->getStructName();
                break;
            case ItemType::MAP;
                $str = 'array';
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $sub_type = self::varType($sub_item);
                $str = $sub_type . '[]';
                break;
            case ItemType::INT:
                $str = 'int';
                break;
            case ItemType::BOOL:
                $str = 'bool';
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
        $this->loadTpl($class_file, 'tpl/class.tpl');
        /** @var StrBuf $class_name_buf */
        $class_name_buf = $class_file->getBuf('php_class');
        if (null === $class_name_buf) {
            throw new Exception('Can not found class name buf');
        }
        $class_name_buf->pushStr($main_class_name);
        //模板中的变量处理
        $class_file->setVariableValue('namespace', $this->joinNameSpace($name_space));
        $class_file->setVariableValue('struct_note', ' ' . $struct->getNote());

        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $method_buf = $class_file->getBuf(FileBuf::METHOD_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        if (!$method_buf || !$property_buf || !$use_buf) {
            throw new Exception('Tpl error, METHOD_BUF or PROPERTY_BUF or IMPORT_BUF not found!');
        }
        $use_buf->setPrefixEmptyLine();
        $property_buf->setPrefixEmptyLine();
        $this->readClassConfig($class_file, $struct);
        $item_list = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            $this->makeImportCode($item, $name_space, $use_buf);
            $property_buf->pushStr('/**');
            $item_type = self::varType($item);
            $property_desc_buf = new StrBuf();
            $property_buf->insertBuf($property_desc_buf);
            $property_desc_buf->pushStr(' * @var ' . $item_type);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $property_desc_buf->pushStr(' ' . $tmp_node);
            }
            $property_buf->pushStr(' */');
            $property_line_buf = new StrBuf();
            $property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr('public $' . $this->fixPropertyName($name, $item));
            if ($item->hasDefault()) {
                $property_line_buf->pushStr(' = ' . $item->getDefault());
            }
            $property_line_buf->pushStr(';');
        }
        $this->packMethodCode($class_file, $struct);
        $this->fixClassName($class_name_buf, $class_file);
    }

    /**
     * 生成引用相关的代码
     * @param Item $item
     * @param string $name_space 所在命名空间
     * @param CodeBuf $use_buf
     */
    private function makeImportCode($item, $name_space, $use_buf)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $struct = $item->getStruct();
            $use_ns = $struct->getNamespace();
            if ($use_ns !== $name_space) {
                $use_name_space = $this->joinNameSpace($use_ns, $struct->getClassName());
                $use_buf->pushUniqueStr('use ' . $use_name_space . ';');
            }
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->makeImportCode($item->getItem(), $name_space, $use_buf);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->makeImportCode($item->getValueItem(), $name_space, $use_buf);
        }
    }

    /**
     * 通用文件
     */
    public function buildCommonCode()
    {
        if ($this->build_opt->getConfig('no_autoload_file')) {
            return;
        }
        $main_buf = $this->getFolder()->touch('', self::MAIN_FILE);
        $this->loadTpl($main_buf, 'tpl/dop.tpl');
    }

    /**
     * 按xml文件生成代码
     * @param string $xml_file
     * @param array $ns_struct
     */
    public function codeByXml($xml_file, $ns_struct)
    {
        $autoload_buf = $this->getBuf('', 'dop.php', 'autoload');
        if (!$autoload_buf) {
            return;
        }
        $autoload_buf->pushStr("'\\" . $this->joinNameSpace($xml_file) . "' => \$dop_file_dir . '" . $xml_file . "',");
    }

    /**
     * 路径转全名空间
     * @param string $path
     * @return mixed
     */
    private function pathToNs($path)
    {
        $name_arr = Str::split($path, '/');
        foreach ($name_arr as &$tmp) {
            $tmp = Str::camelName($tmp);
        }
        return join('\\', $name_arr);
    }

    /**
     * 连接命名空间
     * @param string $ns
     * @param string $class_name
     * @param string $separator
     * @return string
     */
    public function joinNameSpace($ns, $class_name = '', $separator = '\\')
    {
        $ns = $this->pathToNs($ns);
        return parent::joinNameSpace($ns, $class_name, $separator);
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
        $path_arr = explode('/', $path);
        foreach ($path_arr as &$tmp) {
            $tmp = Str::camelName($tmp);
        }
        $path = join('/', $path_arr);
        $file_name = $struct->getClassName() . '.php';
        $file = $folder->getFile($path, $file_name);
        if (null === $file) {
            $file = $folder->touch($path, $file_name);
        }
        return $file;
    }

    /**
     * className 生成
     * @param StrBuf $class_name_buf
     * @param FileBuf $file_buf
     */
    public function fixClassName($class_name_buf, $file_buf)
    {
        /** @var StrBuf $extend_buf */
        $extend_buf = $file_buf->getBuf(FileBuf::EXTENDS_BUF);
        if ($extend_buf && !$extend_buf->isEmpty()) {
            $extend_buf->setJoinStr($this->extends_join_char);
            $class_name = $this->fixClassImport($extend_buf->dump(), $file_buf);
            $class_name_buf->pushStr($this->extends_flag . $class_name);
        }
        /** @var StrBuf $implement_buf */
        $implement_buf = $file_buf->getBuf(FileBuf::IMPLEMENT_BUF);
        if ($implement_buf && !$implement_buf->isEmpty()) {
            $implement_buf->setJoinStr($this->implements_join_char);
            $class_name = $this->fixClassImport($implement_buf->dump(), $file_buf);
            $class_name_buf->pushStr($this->implements_flag . $class_name);
        }
    }

    /**
     * 将引用的 命名空间\类名，转成use 方式
     * @param string $full_class_name
     * @param FileBuf $file_buf
     * @return string
     */
    private function fixClassImport($full_class_name, $file_buf)
    {
        $import_buf = $file_buf->getBuf(FileBuf::IMPORT_BUF);
        if (!$import_buf) {
            return $full_class_name;
        }
        $pos = strrpos($full_class_name, '\\');
        if (false === $pos) {
            return $full_class_name;
        }
        $import_buf->pushStr('use ' . ltrim($full_class_name, '\\') . ';');
        return substr($full_class_name, $pos + 1);
    }
}
