<?php

namespace ffan\dop\plugin;

use ffan\dop\CodeBuf;
use ffan\dop\DOPException;
use ffan\dop\GenerateInterface;
use ffan\dop\Item;
use ffan\dop\ProtocolManager;
use ffan\dop\Struct;
use ffan\dop\BuildOption;

/**
 * Class Plugin
 * @package ffan\dop
 */
abstract class Plugin
{
    /**
     * @var string 插件相关属性名的前缀
     */
    protected $attribute_name_prefix;

    /**
     * @var ProtocolManager;
     */
    protected $manager;

    /**
     * @var string 插件名
     */
    protected static $name;

    /**
     * @var null|array 插件配置
     */
    protected $config;

    /**
     * @var bool 是否存在插件的模板
     */
    protected $has_tpl;

    /**
     * @var string 模板文件
     */
    protected $tpl_name;

    /**
     * PluginInterface constructor.
     * @param ProtocolManager $manager
     * @param array $config
     */
    public function __construct(ProtocolManager $manager, $config = null)
    {
        $this->manager = $manager;
        $this->config = $config;
    }

    /**
     * 初始化
     * @param \DOMElement $node
     * @param Item $item
     */
    abstract public function init(\DOMElement $node, Item $item);

    /**
     * 获取插件名称
     * @return string
     * @throws DOPException
     */
    public static function getName()
    {
        if (null === self::$name) {
            throw new DOPException('Property name required!');
        }
        return self::$name;
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
                $msg = $this->manager->fixErrorMsg('v-length:' . $set_str . ' 无效');
                $this->manager->buildLogError($msg);
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
     * 获取插件的配置
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig($key, $default = null)
    {
        if (!isset($this->config[$key])) {
            return $default;
        }
        return $this->config[$key];
    }

    /**
     * 获取某种语言的代码生成实例
     * @param string $code_type
     * @return GenerateInterface
     */
    public function getGenerator($code_type)
    {
        $class_name = $this->codeClassName($code_type);
        $file = __DIR__ . DIRECTORY_SEPARATOR . self::$name . DIRECTORY_SEPARATOR . $class_name . '.php';
        $generator = null;
        if (is_file($file)) {
            $full_class = $this->codeClassName($code_type, true);
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            //类是否存在
            if (class_exists($full_class)) {
                $implements = class_implements($full_class);
                //类是否 实现接口 GenerateInterface
                if (isset($implements['ffan\\dop\\\GenerateInterface'])) {
                    $generator = new $full_class();
                }
            }
        }
        return $generator;
    }

    /**
     * 生成代码的类名
     * @param string $code_type
     * @param bool $ns 是否带全名空间
     * @return string
     */
    private function codeClassName($code_type, $ns = false)
    {
        $class_name = ucfirst($code_type) . ucfirst(self::$name) . 'Code';
        if ($ns) {
            $ns_str = 'ffan\\dop\\plugin\\' . self::$name;
            $class_name = $ns_str . '\\' . $class_name;
        }
        return $class_name;
    }

    /**
     * 获取protocolManager对象
     * @return ProtocolManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}