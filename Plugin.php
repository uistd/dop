<?php
namespace ffan\dop;
use ffan\php\tpl\Tpl;

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
    protected $name;

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
     * @return array
     */
    abstract public function init(\DOMElement $node, Item $item);

    /**
     * 获取插件名称
     * @return string
     * @throws DOPException
     */
    protected function getName()
    {
        if (null === $this->name) {
            throw new DOPException('Property name required!');
        }
        return $this->name;
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
     * 生成代码
     * @param Struct $struct
     * @return string
     */
    abstract public function generateCode(Struct $struct);

    /**
     * 是否存在插件的模板文件
     * @return bool
     */
    protected function hasPluginTpl()
    {
        if (null === $this->has_tpl) {
            $tpl_name = $this->getPluginTplName();
            $this->has_tpl = Tpl::hasTpl($tpl_name);
        }
        return $this->has_tpl;
    }

    /**
     * 获取模板文件
     * @return string
     */
    protected function getPluginTplName()
    {
        if (null === $this->tpl_name) {
            $tpl_type = $this->manager->getBuildTplType();
            $this->tpl_name = $tpl_type . '/plugin_' . $this->name;
        }
        return $this->tpl_name;
    }
}
