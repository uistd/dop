<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\StrBuf;
use ffan\dop\Exception;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\ItemType;
use ffan\dop\protocol\ListItem;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\StructItem;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Coder
 * @package ffan\dop\coder\php
 */
class Coder extends CoderBase
{
    /**
     * 生成后的主文件
     */
    const MAIN_FILE = 'dop.php';

    /**
     * require 路径判断
     * @param string $require_path 引用的类路径
     * @param string $this_ns 当前的域名
     * @return string
     */
    public static function requirePath($require_path, $this_ns)
    {
        static $cache_path = array();
        $class_name = basename($require_path);
        $path = dirname($require_path);
        if ($this_ns === $path) {
            $file_name = $class_name;
        } else {
            //两个目录之间的相对关系增加缓存机制，减少系统开销时间
            $key = $path . ':' . $this_ns;
            if (isset($cache_path[$key])) {
                $relative_path = $cache_path[$key];
            } else {
                $require_path_arr = FFanStr::split($path, '/');
                $current_path_arr = FFanStr::split($this_ns, '/');
                $len = min(count($current_path_arr), count($require_path_arr));
                for ($i = 0; $i < $len; ++$i) {
                    $tmp_path = current($require_path_arr);
                    $tmp_ns = current($current_path_arr);
                    if ($tmp_ns !== $tmp_path) {
                        break;
                    }
                    array_shift($require_path_arr);
                    array_shift($current_path_arr);
                }
                $relative_path = str_repeat('../', count($current_path_arr));
                if (!empty($require_path_arr)) {
                    $relative_path .= join('/', $require_path_arr) . '/';
                }
                $cache_path[$key] = $relative_path;
            }
            $file_name = $relative_path . $class_name;
        }
        return $file_name . '.php';
    }

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
                $str = 'array[' . $sub_type . ']';
                break;
            case ItemType::INT:
                $str = 'int';
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
        $parent_struct = $struct->getParent();
        $main_class_name = $struct->getClassName();
        $name_space = $struct->getNamespace();
        $class_file = $this->getClassFileBuf($struct);
        $this->loadTpl($class_file, 'tpl/class.tpl');
        $class_name_buf = $class_file->getBuf('php_class');
        if (null === $class_name_buf) {
            throw new Exception('Can not found class name buf');
        }
        $class_name_buf->pushStr($main_class_name);
        //模板中的变量处理
        $class_file->setVariableValue('namespace', $this->joinNameSpace($name_space));
        $class_file->setVariableValue('struct_node', ' '. $struct->getNote());
        
        $use_buf = $class_file->getBuf(FileBuf::IMPORT_BUF);
        $property_buf = $class_file->getBuf(FileBuf::PROPERTY_BUF);
        if (!$use_buf || !$property_buf ) {
            throw new Exception('Tpl error, IMPORT_BUF or PROPERTY_BUF not found!');
        }
        //如果有父类，加入父类
        if ($struct->hasExtend()) {
            //如果不是同一个全名空间
            if ($parent_struct->getNamespace() !== $name_space) {
                $use_buf->emptyLine();
                $use_name_space = $this->joinNameSpace($parent_struct->getNamespace(), $parent_struct->getClassName());
                $use_buf->pushStr('use ' . $use_name_space . ';');
            }
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
            $property_buf->pushStr(' * @var ' . $item_type);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $property_desc_buf->pushStr(' ' . $tmp_node);
            }
            $property_buf->pushStr(' */');
            $property_line_buf = new StrBuf();
            $property_buf->insertBuf($property_line_buf);
            $property_line_buf->pushStr('public $' . $name);
            if ($item->hasDefault()) {
                $property_line_buf->pushStr(' = ' . $item->getDefault());
            }
            $property_line_buf->pushStr(';');
        }
        $this->packMethodCode($class_file, $struct);
    }

    /**
     * 通用文件
     */
    public function buildCommonCode()
    {
        $main_buf = $this->getFolder()->touch('', self::MAIN_FILE);
        $this->loadTpl($main_buf, 'tpl/dop.tpl');
        $folder = $this->getFolder();
        $name_space = $this->joinNameSpace('');
        $folder->writeToFile('', Coder::MAIN_FILE, 'autoload', "'" . $name_space . "' => ''," );
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
        $autoload_buf->pushStr("'" . $this->joinNameSpace($xml_file) . "' => '" . $xml_file . "',");
    }

    /**
     * 路径转全名空间
     * @param string $path
     * @return mixed
     */
    private function pathToNs($path)
    {
        return str_replace('/', '\\', $path);
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
        $file_name = $struct->getClassName() .'.php';
        $file = $folder->getFile($path, $file_name);
        if (null === $file) {
            $file = $folder->touch($path, $file_name);
        }
        return $file;
    }
    
}
