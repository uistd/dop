<?php
namespace ffan\dop;

/**
 * Class Struct
 * @package ffan\dop
 */
class Struct
{
    /**
     * 普通struct
     */
    const TYPE_STRUCT = 1;

    /**
     * 请求包
     */
    const TYPE_REQUEST = 2;

    /**
     * 返回包
     */
    const TYPE_RESPONSE = 3;

    /**
     * @var array
     */
    private $item_list = array();

    /**
     * @var string 命名空间
     */
    private $namespace;

    /**
     * @var string
     */
    private $className;

    /**
     * @var bool 是否可以被其它文件调用
     */
    private $is_public;

    /**
     * @var int 类型
     */
    private $type = self::TYPE_STRUCT;

    /**
     * @var Struct
     */
    private $parent;

    /**
     * Struct constructor.
     * @param string $namespace 命名空间
     * @param string $name 类名
     * @param int $type 类型
     * @param bool $is_public 是否可以被其它文件调用
     */
    public function __construct($namespace, $name, $type = self::TYPE_STRUCT, $is_public = false)
    {
        if (!is_string($namespace) || '/' !== $namespace[0]) {
            throw new \InvalidArgumentException('namespace error');
        }
        if ( self::TYPE_STRUCT !== $type && self::TYPE_REQUEST !== $type && self::TYPE_RESPONSE !== $type) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->namespace = $namespace;
        $this->className = $name;
        $this->is_public = (bool)$is_public;
        $this->type = $type;
    }

    /**
     * 添加元素项
     * @param string $name 名称
     * @param Item $item
     * @return bool
     */
    public function addItem($name, Item $item)
    {
        if (isset($this->item_list[$name])) {
            return false;
        }
        $this->item_list[$name] = $item;
        return true;
    }

    /**
     * 设置继承
     * @param Struct $parent_struct
     * @throws DOPException
     */
    public function extend(Struct $parent_struct)
    {
        //如果已经继承了
        if (null !== $this->parent) {
            throw new DOPException('Struct:'. $this->namespace . $this->className .' 不支持多重继承');
        }
        $this->parent = $parent_struct;
    }

    /**
     * 是否存在某个key
     * @param string $name
     * @return bool
     */
    public function hasItem($name)
    {
        return isset($this->item_list[$name]);
    }

    /**
     * 是否公用
     * @return bool
     */
    public function isPublic()
    {
        return $this->is_public;
    }

    /**
     * 获取所在命名空间
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * 获取类名
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * 返回所有Item
     * @return array[Item]
     */
    public function getAllItem()
    {
        return $this->item_list;
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
     * 导出为数组格式，方便生成文件
     * @return array
     */
    public function export()
    {
        $result = array(
            'is_extend' => null === $this->parent,
            'class_name' => $this->className,
            //'item'
        );
        if ($this->parent) {
            $result['parent'] = array(
                'class' => $this->parent->getClassName(),
                'namespace' => $this->parent->getNamespace()
            ); 
        }
        return $result;
    }
}
