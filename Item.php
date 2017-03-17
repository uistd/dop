<?php
namespace ffan\dop;

/**
 * Class Item 协议的每一项
 * @package ffan\dop
 */
abstract class Item
{
    /**
     * @var string 名称
     */
    private $name;

    /**
     * @var int 类型
     */
    protected $type;

    /**
     * @var string 注释
     */
    protected $note = '';

    /**
     * @var ProtocolManager
     */
    protected $protocol_manager;

    /**
     * @var string 默认值
     */
    protected $default;

    /**
     * @var array 插件数据
     */
    protected $plugin_data_arr;

    /**
     * Item constructor.
     * @param string $name 名称
     * @param ProtocolManager $manger
     */
    public function __construct($name, ProtocolManager $manger)
    {
        $this->name = $name;
        $this->protocol_manager = $manger;
    }

    /**
     * 获取元素名称
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置注释
     * @param string $note
     * @throws DOPException
     */
    public function setNote($note)
    {
        if (!is_string($note)) {
            return;
        }
        $this->note = self::fixLine($note);
    }

    /**
     * 设置默认值
     * @param string $value
     * @return void
     */
    abstract public function setDefault($value);

    /**
     * 获取属性的魔术方法
     * @param string $name
     * @return mixed
     * @throws DOPException
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new DOPException('No property ' . $name);
        }
        return $this->$name;
    }

    /**
     * 获取类型
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 将多行转换成1行
     * @param string $str
     * @return string
     */
    public static function fixLine($str)
    {
        static $patten = array("\r\n", "\n", "\r");
        $str = str_replace($patten, '', $str);
        return $str;
    }

    /**
     * 添加插件数据
     * @param string $plugin_name
     * @param array $data
     */
    public function addPluginData($plugin_name, array $data)
    {
        $this->plugin_data_arr[$plugin_name] = $data;
    }
}
