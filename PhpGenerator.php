<?php

namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;
use ffan\php\utils\Str as FFanStr;

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
     * PhpGenerator constructor.
     * @param ProtocolManager $protocol_manager
     */
    public function __construct(ProtocolManager $protocol_manager)
    {
        parent::__construct($protocol_manager);
        /*
        //注册一些私有的修正器
        //命名空间
        Tpl::registerGrep('php_ns', array($this, 'phpNameSpace'));
        //类型
        Tpl::registerGrep('php_var_type', array('ffan\dop\PhpGenerator', 'varType'));
        //变量值初始化
        Tpl::registerPlugin('php_item_init', array('ffan\dop\PhpGenerator', 'phpItemInit'));
        //数据导出成数组
        Tpl::registerPlugin('php_export_array', array('ffan\dop\PhpGenerator', 'phpExportArray'));
        //检查是不是非常 简单的类型
        Tpl::registerGrep('php_simple_type', array('ffan\dop\PhpGenerator', 'isSimpleType'));
        //是否需要检查是否需要判断数组
        Tpl::registerGrep('php_array_check', function ($type) {
            return ItemType::ARR === $type || ItemType::MAP === $type || ItemType::STRUCT === $type;
        });
        //类型强转
        Tpl::registerGrep('php_convert_value', array('ffan\dop\PhpGenerator', 'convertValue'));
        //require 路径
        Tpl::registerGrep('php_require', array('ffan\dop\PhpGenerator', 'requirePath'));
        */
    }

    /**
     * @param string $type
     * @return string
    public static function convertValue($type)
     * {
     * $re = '';
     * switch ($type) {
     * case ItemType::FLOAT:
     * $re = '(float)';
     * break;
     * case ItemType::INT:
     * $re = '(int)';
     * break;
     * case ItemType::STRING:
     * $re = '(string)';
     * break;
     * }
     * return $re;
     * }
     *
     * /**
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
     * 是否是简单的类型
     * 简单类型就可以直接赋值
     * @param int $type
     * @return bool
     */
    public static function isSimpleType($type)
    {
        return ItemType::BINARY === $type || ItemType::FLOAT === $type || ItemType::STRING === $type || ItemType::INT === $type;
    }

    /**
     * 变更初始化
     * @param array $args
     * @return string
    public static function phpItemInit($args)
     * {
     * return Tpl::get('php/item_init.tpl', $args);
     * }*/

    /**
     * 导出为数组
     * @param array $args
     * @return string
    public static function phpExportArray($args)
     * {
     * return Tpl::get('php/export_array.tpl', $args);
     * }
     */
    /**
     * PHP命名空间的修正器
     * @param string $ns
     * @return mixed|string
     */
    public function phpNameSpace($ns)
    {
        $main_ns = $this->protocol_manager->getMainNameSpace();
        if (!empty(is_string($main_ns))) {
            $ns = $main_ns . $ns;
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
     * 整理生成文件的参数
     * @return array
     */
    protected function buildTplData()
    {
        $build_arg = array(
            'path_define_var' => $this->protocol_manager->getConfig('path_define_var', 'DOP_PATH'),
            'build_path' => $this->protocol_manager->getBuildPath(),
            'code_namespace' => 'namespace',
            'code_php_tag' => "<?php\n"
        );
        return $build_arg;
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
     * @param array $tpl_data 模板数据
     * @throws DOPException
     */
    protected function generateFile($namespace, $class_list, $tpl_data)
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
        print_r($this->protocol_manager->getAllFileList());
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
        $php_class->emptyLine();

        //所有依赖的对象
        $import_class = $struct->getImportStruct();
        foreach ($import_class as $class_name) {
            $php_class->push('require_once \'' . self::requirePath($class_name, $name_space) . '\';');
        }
        //如果有父类，加入父类
        if ($struct->hasExtend()) {

            $php_class->push('require_once \'' . self::requirePath($parent_struct->getFullName(), $name_space) . '\';');
            //如果不是同一个全名空间
            if ($parent_struct->getNamespace() !== $name_space) {
                $php_class->emptyLine();
                $use_name_space = self::phpNameSpace($parent_struct->getNamespace()) . '\\' . $parent_struct->getClassName();
                $php_class->push('use ' . $use_name_space . ';');
            }
        }
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
        $item_list = $struct->getAllItem();
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
        $php_class->indentDecrease();
        $php_class->push('}')->emptyLine();
        return $php_class->dump();
    }
}