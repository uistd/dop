<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;

/**
 * Class Struct
 * @package ffan\dop\protocol
 */
class Struct
{
    /**
     * 普通struct
     */
    const TYPE_STRUCT = 3;

    /**
     * 请求包
     */
    const TYPE_REQUEST = 2;

    /**
     * 返回包
     */
    const TYPE_RESPONSE = 1;

    /**
     * 普通数据
     */
    const TYPE_DATA = 4;

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
     * @var string 注释
     */
    private $note;

    /**
     * @var string 所在文件
     */
    private $file;

    /**
     * @var int 被引用的类型
     */
    private $refer_type = 0;

    /**
     * @var array 所有item包括继承的
     */
    private $all_extend_item;

    /**
     * @var bool 是否是从缓存加载的
     */
    private $load_from_cache = false;

    /**
     * Struct constructor.
     * @param string $namespace 命名空间
     * @param string $name 类名
     * @param string $file 所在的文件
     * @param int $type 类型
     * @param bool $is_public 是否可以被其它文件调用
     */
    public function __construct($namespace, $name, $file, $type = self::TYPE_STRUCT, $is_public = false)
    {
        if (!is_string($namespace) || '/' !== $namespace[0]) {
            throw new \InvalidArgumentException('namespace error');
        }
        $this->file = str_replace('.xml', '', $file);
        $this->namespace = $namespace;
        $this->className = $name;
        $this->is_public = (bool)$is_public;
        $this->type = $type;
    }

    /**
     * 是不是从缓存加载的
     * @return bool
     */
    public function loadFromCache()
    {
        return $this->load_from_cache;
    }

    /**
     * 标记是否从缓存加载标志
     * @param bool $flag
     */
    public function setCacheFlag($flag = true)
    {
        $this->load_from_cache = (bool)$flag;
    }

    /**
     * 增加一种被引用的类型
     * @param int $type 类型
     */
    public function addReferType($type)
    {
        $this->refer_type |= $type;
    }

    /**
     * 是否被指定的类型引用
     * @param int $type
     * @return bool
     */
    public function hasReferType($type)
    {
        return ($this->refer_type & $type) === $type;
    }

    /**
     * 获取refer type
     * @return int
     */
    public function getReferType()
    {
        return $this->refer_type;
    }

    /**
     * 获取所在的文件
     * @param bool $with_extend
     * @return string
     */
    public function getFile($with_extend = true)
    {
        $result = $this->file;
        if ($with_extend) {
            $result .= '.xml';
        }
        return $result;
    }

    /**
     * 注释
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = Item::fixLine(trim($note));
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
     * @throws Exception
     */
    public function extend(Struct $parent_struct)
    {
        //如果已经继承了
        if (null !== $this->parent) {
            throw new Exception('Struct:' . $this->namespace . $this->className . ' 不支持多重继承');
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
     * 返回所有的Item包括Extend的Item
     * @return array
     */
    public function getAllExtendItem()
    {
        if (!$this->parent) {
            return $this->item_list;
        }
        if ($this->all_extend_item) {
            return $this->all_extend_item;
        }
        $item_list = $this->item_list;
        $parent_item = $this->parent->getAllExtendItem();
        $item_list += $parent_item;
        $this->all_extend_item = $item_list;
        return $item_list;
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
     * 是否是子struct
     * @return bool
     */
    public function isSubStruct()
    {
        return self::TYPE_STRUCT === $this->type;
    }

    /**
     * 获取全路径名
     * @return string
     */
    public function getFullName()
    {
        return $this->namespace . '/' . $this->className;
    }

    /**
     * 获取需要import的其它struct
     * @return array
     */
    public function getImportStruct()
    {
        $import_struct = array();
        foreach ($this->item_list as $name => $item) {
            //如果是struct
            if (ItemType::STRUCT === $item->getType()) {
                /** @var StructItem $item */
                $struct = $item->getStruct();
                $import_struct[$struct->getFullName()] = true;
            }
        }
        return array_keys($import_struct);
    }

    /**
     * 是否子类
     * @return bool
     */
    public function hasExtend()
    {
        return null !== $this->parent;
    }

    /**
     * 获取父类
     * @return Struct|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * 获取注释信息
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }
}
