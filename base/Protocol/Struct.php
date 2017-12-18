<?php

namespace UiStd\Dop\Protocol;

use UiStd\Dop\Exception;
use UiStd\Dop\Schema\Model;

/**
 * Class Struct
 * @package UiStd\Dop\Protocol
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
     * @var array 所有item包括继承的
     */
    private $all_extend_item;

    /**
     * @var bool 是否是从缓存加载的
     */
    private $load_from_cache = false;

    /**
     * @var Model 所在的节点
     */
    private $model_schema;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int id 自增
     */
    private static $id_count;

    /**
     * @var array 记录每个packer需要生成的method
     */
    private $packer_method;

    /**
     * @var Struct[] 所有依赖的其它 struct
     */
    private $require_struct;

    /**
     * @var string[] 附加生成packer方法
     */
    private $extra_packer;

    /**
     * @var int 版本号
     */
    private $version = 1;

    /**
     * @var string 方法 request struct使用
     */
    private $method;

    /**
     * @var string uri
     */
    private $uri;

    /**
     * Struct constructor.
     * @param string $namespace 命名空间
     * @param string $name 类名
     * @param string $file 所在的文件
     * @param int $type 类型
     */
    public function __construct($namespace, $name, $file, $type = self::TYPE_STRUCT)
    {
        if (!is_string($namespace) || '/' !== $namespace[0]) {
            throw new \InvalidArgumentException('namespace error');
        }
        $this->file = str_replace('.xml', '', $file);
        $this->namespace = $namespace;
        $this->className = $name;
        $this->id = self::$id_count++;
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
     * 获取一项
     * @param string $name
     * @return Item|null
     */
    public function getItem($name)
    {
        return isset($this->item_list[$name]) ? $this->item_list[$name] : null;
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
     * 获取类型的名称
     * @param string $type
     * @return string
     */
    public static function getTypeName($type)
    {
        switch ($type) {
            case self::TYPE_REQUEST:
                return 'request';
            case self::TYPE_RESPONSE:
                return 'response';
            case self::TYPE_DATA:
                return 'data';
            default:
                return 'struct';
        }
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
     * @return Item[]
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

    /**
     * 设置所在的节点
     * @param Model $model_schema
     */
    public function setModelSchema(Model $model_schema)
    {
        $this->model_schema = $model_schema;
    }

    /**
     * 获取所在节点
     * @return Model|null
     */
    public function getModelSchema()
    {
        return $this->model_schema;
    }

    /**
     * 增加packer method
     * @param string $name
     * @param int $method
     */
    public function addPackerMethod($name, $method)
    {
        if (!isset($this->packer_method[$name])) {
            $this->packer_method[$name] = 0;
        }
        $this->packer_method[$name] |= $method;
        if ($this->require_struct) {
            foreach ($this->require_struct as $id => $struct) {
                $struct->addPackerMethod($name, $method);
            }
        }
    }

    /**
     * 是否有某个packer的method
     * @param string $name
     * @param int $method
     * @return bool
     */
    public function hasPackerMethod($name, $method)
    {
        return isset($this->packer_method[$name]) && ($this->packer_method[$name] & $method) > 0;
    }

    /**
     * 设置依赖其它 struct
     * @param Struct $struct
     */
    public function addRequireStruct(Struct $struct)
    {
        $this->require_struct[$struct->id] = $struct;
    }

    /**
     * 加载依赖的struct
     * @return Struct[]
     */
    public function getRequireStruct()
    {
        if (null === $this->require_struct) {
            return [];
        }
        return $this->require_struct;
    }

    /**
     * 增加附加packer
     * @param string $name
     */
    public function addExtraPacker($name)
    {
        if (isset($this->extra_packer[$name])) {
            return;
        }
        $this->extra_packer[$name] = true;
    }

    /**
     * 是否满足某个extra packer
     * @param string $name
     * @return bool
     */
    public function isSetExtraPacker($name)
    {
        return isset($this->extra_packer[$name]);
    }

    /**
     * 版本号
     * @param int $ver
     */
    public function setVersion($ver)
    {
        $this->version = (int)$ver;
        if ($this->version < 1) {
            $this->version = 1;
        }
    }

    /**
     * 获取版本号
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 设置请求方法
     * @param $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 设置uri
     * @param $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * 获取uri
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * 忽略版本号
     * @param string $ns
     * @return string
     */
    public static function ignoreVersion($ns)
    {
        return preg_replace('#_v[\d]+$#', '', $ns);
    }

    /**
     * 重置命名空间，忽略版本号
     */
    public function resetNameSpaceIgnoreVersion()
    {
        if (1 === $this->version) {
            return;
        }
        $this->namespace = self::ignoreVersion($this->namespace);
        $this->file = self::ignoreVersion($this->file);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
