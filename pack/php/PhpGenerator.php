<?php

namespace ffan\dop\pack\php;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\DOPException;
use ffan\dop\DOPGenerator;
use ffan\dop\Item;
use ffan\dop\ItemType;
use ffan\dop\ListItem;
use ffan\dop\Struct;
use ffan\dop\StructItem;
use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Str as FFanStr;
use ffan\php\tpl\Tpl as FFanTpl;

/**
 * Class PhpGenerator
 * @package ffan\dop
 */
class PhpGenerator extends DOPGenerator
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
     * PHP命名空间的修正器
     * @param string $ns
     * @return mixed|string
     */
    public function phpNameSpace($ns)
    {
        $prefix = $this->build_opt->namespace_prefix;
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
     * 生成文件
     * @param string $namespace 命令空间
     * @param array [Struct] $class_list
     * @throws DOPException
     */
    protected function generateFile($namespace, $class_list)
    {
        $base_path = $this->buildBasePath();
        $build_path = FFanUtils::joinPath($base_path, $namespace);
        FFanUtils::pathWriteCheck($build_path);
        /**
         * @var string $class_name
         * @var Struct $struct
         */
        foreach ($class_list as $class_name => $struct) {
            if (!$struct->needBuild()) {
                continue;
            }
            $result = $this->make($struct);
            $file_name = $this->buildFileName($build_path, $struct);
            $re = file_put_contents($file_name, $result);
            if (!$re) {
                throw new DOPException('Can not put contents to file:' . $file_name);
            }
            $this->protocol_manager->buildLog('Generate file:' . $file_name);
        }
    }

    /**
     * 通用文件生成
     */
    protected function generateCommon()
    {
        //如果是手动require文件，那就不生成dop.php文件
        if ($this->build_opt->php_require_file) {
            return;
        }
        $all_files = $this->protocol_manager->getAllFileList();
        $prefix = $this->build_opt->namespace_prefix;
        $autoload_set = array();
        foreach ($all_files as $file => $m) {
            //除去.xml，其它 就是路径信息
            $path = substr($file, 0, -4);
            $ns = $prefix . '\\' . str_replace('/', '\\', $path);
            $autoload_set[$ns] = $path;
        }
        $this->initTpl();
        $file_content = FFanTpl::get('php/dop.tpl', array(
            'namespace_set' => $autoload_set
        ));
        $build_path = $this->buildBasePath();
        $file = $build_path . 'dop.php';
        file_put_contents($file, $file_content);
    }

    /**
     * 生成PHP代码文件
     * @param Struct $struct
     * @return string
     */
    private function make($struct)
    {
        $php_class = new CodeBuf();
        $name_space = $struct->getNamespace();
        $php_class->push('<?php');
        $main_class_name = $struct->getClassName();
        $parent_struct = $struct->getParent();
        $php_class->emptyLine();
        $ns = $this->phpNameSpace($name_space);
        $php_class->push('namespace ' . $ns . ';');
        // 如果手动require
        if ($this->build_opt->php_require_file) {
            //所有依赖的对象
            $import_class = $struct->getImportStruct();
            foreach ($import_class as $class_name) {
                $php_class->push('require_once \'' . self::requirePath($class_name, $name_space) . '\';');
            }
        }

        //如果有父类，加入父类
        if ($struct->hasExtend()) {
            if ($this->build_opt->php_require_file) {
                $php_class->push('require_once \'' . self::requirePath($parent_struct->getFullName(), $name_space) . '\';');
            }
            //如果不是同一个全名空间
            if ($parent_struct->getNamespace() !== $name_space) {
                $php_class->emptyLine();
                $use_name_space = self::phpNameSpace($parent_struct->getNamespace()) . '\\' . $parent_struct->getClassName();
                $php_class->push('use ' . $use_name_space . ';');
            }
        }
        $php_class->emptyLine();
        $php_class->push('/**');
        $node_str = $struct->getNote();
        $php_class->lineTmp(' * ' . $main_class_name);
        if (!empty($node_str)) {
            $php_class->lineTmp(' ' . $node_str);
        }
        $php_class->lineFin();
        $php_class->push(' */');
        $php_class->lineTmp('class ' . $main_class_name);
        if ($struct->hasExtend()) {
            $php_class->lineTmp(' extends ' . $parent_struct->getClassName());
        }
        $php_class->lineFin();
        $php_class->push('{');
        //缩进
        $php_class->indentIncrease();
        $item_list = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($item_list as $name => $item) {
            $php_class->push('/**');
            $item_type = self::varType($item);
            $php_class->push(' * @var ' . $item_type . ' ' . $item->getNote());
            $php_class->push(' */');
            $php_class->lineTmp('public $' . $name);
            if ($item->hasDefault()) {
                $php_class->lineTmp(' = ' . $item->getDefault());
            }
            $php_class->lineTmp(';')->lineFin()->emptyLine();
        }
        $struct_type = $struct->getType();
        //如果需要生成 encode 方法
        if ($this->isBuildPackMethod($struct_type)) {
            $this->buildPackMethod($php_class, $struct);
        }
        //如果需要生成 decode 方法
        if ($this->isBuildUnpackMethod($struct_type)) {
            $this->buildUnpackMethod($php_class, $struct);
        }
        //其它插件相关代码
        $this->pluginCode($php_class, $struct);
        $php_class->indentDecrease();
        $php_class->push('}');
        return $php_class->dump();
    }

    /**
     * 生成打包方法
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    private function buildPackMethod($code_buf, $struct)
    {
        //json
        if ($this->build_opt->pack_type & BuildOption::PACK_TYPE_JSON) {
            JsonPack::buildPackMethod($struct, $code_buf);
        }
    }

    /**
     * 生成解包方法
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    private function buildUnpackMethod($code_buf, $struct)
    {
        //json
        if ($this->build_opt->pack_type & BuildOption::PACK_TYPE_JSON) {
            JsonPack::buildUnpackMethod($struct, $code_buf);
        }
    }
}