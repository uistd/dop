<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\dop\protocol\Item;

/**
 * Class PluginBase 插件基类
 * @package ffan\dop\build
 */
abstract class PluginBase
{
    /**
     * @var string 插件相关属性名的前缀
     */
    protected $attribute_name_prefix;

    /**
     * @var Manager;
     */
    protected $manager;

    /**
     * @var string 插件名
     */
    protected $name;

    /**
     * @var bool 是否存在插件的模板
     */
    protected $has_tpl;

    /**
     * @var string 模板文件
     */
    protected $tpl_name;

    /**
     * @var array 插件处理
     */
    private $handler_list = array();

    /**
     * @var string 所在路径
     */
    private $base_path;

    /**
     * PluginInterface constructor.
     * @param Manager $manager
     * @param string $name
     */
    public function __construct(Manager $manager, $name)
    {
        $this->manager = $manager;
        $this->name = $name;
        $this->base_path = $manager->getPluginMainPath($this->name);
        $this->initHandler();
    }

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     */
    abstract public function init(\DOMElement $node, Item $item);

    /**
     * 初始化处理器
     */
    private function initHandler()
    {
        $dir_name = $this->base_path .DIRECTORY_SEPARATOR . 'handler/';
        $dir_handle = readdir($dir_name);
        while (false != ($file = readdir($dir_handle))) {
            $tmp_name = $dir_name . $file;
            if ('.' === $file{0} || !is_file($tmp_name) || '.php' !== substr($file, -4)) {
                continue;
            }
        }
        $name = basename($file, '.php');
        $class_file = $dir_name . $file;
        $this->registerHandler($name, $class_file);
    }
    
    /**
     * 获取插件名称
     * @return string
     * @throws Exception
     */
    public function getName()
    {
        if (null === $this->name) {
            throw new Exception('Property name required!');
        }
        return $this->name;
    }

    /**
     * 注册一个插件处理器
     * @param string $coder_name 代码生成器名称
     * @param string $class_file
     * @throws Exception
     */
    public function registerHandler($coder_name, $class_file)
    {
        if (isset($this->handler_list[$coder_name])) {
            throw new Exception('Plugin '. $this->name .' coder:'. $coder_name .' exist!');
        }
        $this->handler_list[$coder_name] = $class_file;
    }

    /**
     * 字符串长度限制
     * @param \DOMElement $node
     * @param string $name
     * @param bool $is_int
     * @return array|null
     */
    protected function readSplitSet($node, $name, $is_int = true)
    {
        $set_str = $this->read($node, $name);
        $min = null;
        $max = null;
        if (!empty($set_str)) {
            if (false === strpos($set_str, ',')) {
                $max = $set_str;
            } else {
                $tmp = explode(',', $set_str);
                $min = trim($tmp[0]);
                $max = trim($tmp[1]);
            }
            if ($is_int) {
                $min = (int)$min;
                $max = (int)$max;
            } else {
                $min = (float)$min;
                $max = (float)$max;
            }
            if ($max < $min) {
                $this->manager->buildLogError('v-length:' . $set_str . ' 无效');
                $max = $min = null;
            }
        }
        return [$min, $max];
    }

    /**
     * 读取一个bool值
     * @param \DOMElement $node
     * @param string $name
     * @param bool $default 默认值
     * @return bool
     */
    protected function readBool($node, $name, $default = false)
    {
        $set_str = $this->read($node, $name, $default);
        return (bool)$set_str;
    }

    /**
     * 读取一条规则
     * @param \DOMElement $node
     * @param string $name 规则名
     * @param null $default
     * @return mixed
     */
    protected function read($node, $name, $default = null)
    {
        $attr_name = $this->attributeName($name);
        if (!$node->hasAttribute($attr_name)) {
            return $default;
        }
        return trim($node->getAttribute($attr_name));
    }

    /**
     * 属性名称
     * @param string $name 属性名
     * @return string
     */
    protected function attributeName($name)
    {
        if (null !== $this->attribute_name_prefix) {
            $result = $this->attribute_name_prefix;
        } else {
            $result = '';
        }
        if (!empty($name)) {
            $result .= '-';
        }
        return $result . $name;
    }

    /**
     * 获取某种语言的代码生成实例
     * @param string $code_type
     * @return PluginHandlerBase
     */
    public function getPluginCoder($code_type)
    {
        $class_name = $this->codeClassName($code_type);
        $file = __DIR__ . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . $class_name . '.php';
        $coder = null;
        if (is_file($file)) {
            $full_class = $this->codeClassName($code_type, true);
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            //类是否存在
            if (class_exists($full_class)) {
                $parents = class_parents($full_class);
                //类是否 继续 PluginCoder
                if (isset($parents['ffan\dop\PluginCoder'])) {
                    $coder = new $full_class();
                }
            }
        }
        return $coder;
    }

    /**
     * 生成代码的类名
     * @param string $code_type
     * @param bool $ns 是否带全名空间
     * @return string
     */
    private function codeClassName($code_type, $ns = false)
    {
        $class_name = ucfirst($code_type) . ucfirst($this->name) . 'Code';
        if ($ns) {
            $ns_str = 'ffan\dop\plugin\\' . $this->name;
            $class_name = $ns_str . '\\' . $class_name;
        }
        return $class_name;
    }

    /**
     * 获取protocolManager对象
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
