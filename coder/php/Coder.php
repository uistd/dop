<?php

namespace ffan\dop\coder\php;

use ffan\dop\build\BuildOption;
use ffan\dop\build\CodeBuf;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\StrBuf;
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
     * @var string 模板文件
     */
    protected $tpl = 'php/php.tpl';

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
     * PHP命名空间
     * @param BuildOption $build_opt
     * @param string $ns
     * @return string
     */
    public static function phpNameSpace($build_opt, $ns)
    {
        $prefix = $build_opt->namespace_prefix;
        if (is_string($prefix)) {
            $ns = $prefix . $ns;
        }
        $ns = str_replace('/', '\\', $ns);
        return $ns;
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
     * 生成文件名
     * @param string $build_path
     * @param Struct $struct
     * @return string
     */
    protected function buildFileName($build_path, Struct $struct)
    {
        $class_name = $struct->getClassName();
        return $build_path . $class_name . '.php';
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     */
    public function buildStructCode($struct)
    {
        $main_class_name = $struct->getClassName();
        $name_space = $struct->getNamespace();
        $build_opt = $this->build_opt;
        $file_buf = new FileBuf($name_space .'/'. $main_class_name . '.php');
        $this->builder->addFile($file_buf);
        $class_buf = $file_buf->getMainBuf();
        $class_buf->push('<?php');
        $parent_struct = $struct->getParent();
        $class_buf->emptyLine();
        $ns = self::phpNameSpace($build_opt, $name_space);
        $class_buf->push('namespace ' . $ns . ';');
        $use_buf = new CodeBuf();
        $file_buf->addBuf(FileBuf::IMPORT_BUF, $use_buf);
        //如果有父类，加入父类
        if ($struct->hasExtend()) {
            //如果不是同一个全名空间
            if ($parent_struct->getNamespace() !== $name_space) {
                $use_buf->emptyLine();
                $use_name_space = self::phpNameSpace($build_opt, $parent_struct->getNamespace()) . '\\' . $parent_struct->getClassName();
                $use_buf->push('use ' . $use_name_space . ';');
            }
        }
        $class_buf->emptyLine();
        $class_buf->push('/**');
        $node_str = $struct->getNote();
        $class_desc_buf = new StrBuf();
        $class_buf->insertBuf($class_desc_buf);
        $class_desc_buf->push(' * '. $main_class_name);
        if (!empty($node_str)) {
            $class_desc_buf->push(' ' . $node_str);
        }
        $class_buf->push(' */');
        $class_name_buf = new StrBuf();
        $class_buf->insertBuf($class_name_buf);
        $class_name_buf->push('class '. $main_class_name);
        if ($struct->hasExtend()) {
            $class_name_buf->push(' extends ' . $parent_struct->getClassName());
        }
        $class_buf->push('{');
        //缩进
        $class_buf->indentIncrease();
        $item_list = $struct->getAllExtendItem();
        $property_buf = new CodeBuf();
        $file_buf->addBuf(FileBuf::PROPERTY_BUF, $property_buf);
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            $property_buf->push('/**');
            $item_type = self::varType($item);
            $property_desc_buf = new StrBuf();
            $property_buf->insertBuf($property_desc_buf);
            $property_buf->push(' * @var ' . $item_type);
            $tmp_node = $item->getNote();
            if (!empty($tmp_node)) {
                $property_desc_buf->push(' ' . $tmp_node);
            }
            $property_buf->push(' */');
            $property_line_buf = new StrBuf();
            $property_buf->insertBuf($property_line_buf);
            $property_line_buf->push('public $' . $name);
            if ($item->hasDefault()) {
                $property_line_buf->push(' = ' . $item->getDefault());
            }
            $property_line_buf->push(';');
            $property_buf->emptyLine();
        }
        $method_buf = new CodeBuf();
        $file_buf->addBuf(FileBuf::METHOD_BUF, $method_buf);
        $this->packMethodCode($file_buf, $struct);
        $class_buf->indentDecrease();
        $class_buf->push('}')->emptyLine();
    }

    /**
     * 生成文件结束
     * @return CodeBuf|null

    public function codeFinish()
     * {
     * $build_opt = $this->build_opt;
     * $generator = $this->generator;
     * //如果是手动require文件，那就不生成dop.php文件
     * if ($build_opt->php_require_file) {
     * return null;
     * }
     * $manager = $generator->getManager();
     * $all_files = $manager->getAllFileList();
     * $prefix = $build_opt->namespace_prefix;
     * $autoload_set = array();
     * foreach ($all_files as $file => $m) {
     * //除去.xml，其它 就是路径信息
     * $path = substr($file, 0, -4);
     * $ns = $prefix . '\\' . str_replace('/', '\\', $path);
     * $autoload_set[$ns] = $path;
     * }
     * $file_content = FFanTpl::get('php/dop.tpl', array(
     * 'namespace_set' => $autoload_set
     * ));
     * $build_path = $generator->getBuildBasePath();
     * $file = $build_path . 'dop.php';
     * file_put_contents($file, '<?php' . PHP_EOL . $file_content);
     * return null;
     * }*/
}