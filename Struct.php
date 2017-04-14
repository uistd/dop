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
     * @var bool 是否需要编译
     */
    private $need_build = true;

    /**
     * @var int 被引用的类型
     */
    private $refer_type = 0;

    /**
     * @var array 所有item包括继承的
     */
    private $all_extend_item;

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
        if (self::TYPE_STRUCT !== $type && self::TYPE_REQUEST !== $type && self::TYPE_RESPONSE !== $type) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->file = $file;
        $this->namespace = $namespace;
        $this->className = $name;
        $this->is_public = (bool)$is_public;
        $this->type = $type;
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
     * 设置是否需要编译的标志
     * @param bool $flag
     */
    public function setNeedBuild($flag)
    {
        $this->need_build = (bool)$flag;
    }

    /**
     * 获取所在的文件
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * 是否需要编译
     * @return bool
     */
    public function needBuild()
    {
        return $this->need_build;
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
     * @throws DOPException
     */
    public function extend(Struct $parent_struct)
    {
        //如果已经继承了
        if (null !== $this->parent) {
            throw new DOPException('Struct:' . $this->namespace . $this->className . ' 不支持多重继承');
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
     * @return array[Item]
     */
    public function getAllExtendItem()
    {
        if (!$this->parent) {
            return $this->item_list;
        }
        if (!$this->all_extend_item) {
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
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * 导出为数组格式，方便生成文件
     * @return array

    public function export()
     * {
     *
     *
     * foreach ($this->item_list as $name => $item) {
     * $type = $item->getType();
     * if (ItemType::ARR === $type) {
     *
     * $item = $item->getItem();
     * } elseif (ItemType::MAP === $type) {
     *
     * $item = $item->getValueItem();
     * }
     * //如果是struct
     * if (ItemType::STRUCT === $item->getType()) {
     *
     * $struct = $item->getStruct();
     * $import_struct[$struct->getFullName()] = true;
     * }
     * }
     * $result = array(
     * 'is_extend' => null !== $this->parent,
     * 'class_name' => $this->className,
     * 'note' => $this->note,
     * 'type' => $this->type,
     * 'item_list' => $this->getAllItem(),
     * 'extend_item_list' => $this->getAllExtendItem(),
     * 'namespace' => $this->namespace,
     * 'import_struct' => $import_struct,
     * 'self' => $this
     * );
     * if ($this->parent) {
     * $result['parent'] = array(
     * 'class' => $this->parent->getClassName(),
     * 'namespace' => $this->parent->getNamespace(),
     * 'full_name' => $this->parent->getFullName()
     * );
     * }
     * return $result;
     * }
     */
}
