<?php

namespace UiStd\Dop\Schema;

use UiStd\Dop\Build\BufTrigger;
use UiStd\Dop\Build\BuildOption;
use UiStd\Dop\Exception;
use UiStd\Dop\Manager;
use UiStd\Dop\Protocol\BinaryItem;
use UiStd\Dop\Protocol\BoolItem;
use UiStd\Dop\Protocol\DoubleItem;
use UiStd\Dop\Protocol\FloatItem;
use UiStd\Dop\Protocol\IntItem;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Dop\Protocol\ListItem;
use UiStd\Dop\Protocol\MapItem;
use UiStd\Dop\Protocol\Item as ProtocolItem;
use UiStd\Dop\Protocol\StringItem;
use UiStd\Dop\Protocol\Struct;
use UiStd\Dop\Protocol\StructItem;
use UiStd\Common\Str;
use UiStd\Dop\Build\Shader as BuildShader;

/**
 * Class Protocol
 * @package UiStd\Dop\Schema
 */
class Protocol
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var Model[]
     */
    private $all_model;

    /**
     * @var Shader[]
     */
    private $shader_list;

    /**
     * @var Struct[] 已经完成的
     */
    private $struct_list;

    /**
     * @var array 按namespace分组的model[]
     */
    private $namespace_struct_list;

    /**
     * @var BuildOption
     */
    private $build_opt;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var array 继承堆栈
     */
    private $extend_stack;

    /**
     * @var array 名称
     */
    private $name_stack;

    /**
     * @var array 文件间的依赖
     */
    private $file_require_arr;

    /**
     * @var bool 是否使用action name. 不在request类加自动加 Request
     */
    private $is_keep_action_name;

    /**
     * @var array define
     */
    private $define_list;

    /**
     * Protocol constructor.
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        $this->build_opt = $manager->getCurrentBuildOpt();
        $this->is_keep_action_name = (bool)$this->build_opt->getConfig('keep_action_name', false);
    }

    /**
     * @param Manager $manager
     * @return Protocol
     */
    public static function getInstance(Manager $manager)
    {
        if (!self::$instance) {
            self::$instance = new self($manager);
        }
        return self::$instance;
    }

    /**
     * 添加model
     * @param string $namespace
     * @param Model $model
     * @throws Exception
     */
    public function addModel($namespace, Model $model)
    {
        $full_name = $namespace . '/' . $model->getName();
        if (isset($this->all_model[$full_name])) {
            throw new Exception($full_name . ' 名字冲突');
        }
        $this->all_model[$full_name] = $model;
    }

    /**
     * 添加shader
     * @param Shader $shader
     */
    public function addShader(Shader $shader)
    {
        $this->shader_list[] = $shader;
    }

    /**
     * @param string $full_name
     * @return Model
     */
    public function getModel($full_name)
    {
        return isset($this->all_model[$full_name]) ? $this->all_model[$full_name] : null;
    }

    /**
     * 生成Struct
     */
    public function makeStruct()
    {
        foreach ($this->all_model as $full_name => $model) {
            $this->parseStruct($model);
        }
        if (!empty($this->shader_list)) {
            foreach ($this->shader_list as $shader) {
                Exception::pushStack('Parse shader');
                $this->parseShader($shader);
                Exception::popStack();
            }
        }
        $this->manager->getCache()->set('file_require', $this->file_require_arr);
    }

    /**
     * parseShader
     * @param Shader $shader
     */
    private function parseShader($shader)
    {
        $shader = new BuildShader($this->manager, $shader);
        $this->manager->addShader($shader);
    }

    /**
     * 解析Model
     * @param Model $model
     * @return Struct
     * @throws Exception
     */
    private function parseStruct($model)
    {
        $full_name = $model->getNameSpace() . '/' . $model->getName();
        if (isset($this->struct_list[$full_name])) {
            return $this->struct_list[$full_name];
        }
        Exception::pushStack($model->getDoc());
        if (isset($this->extend_stack[$full_name])) {
            throw new Exception('检测到循环继承');
        }
        $this->extend_stack[$full_name] = true;
        $keep_name_attr = 'keep_name';
        //保持 原始字段 命名的权重
        $item_name_keep_original_weight = (int)$this->build_opt->isKeepOriginalName();
        //如果有在struct指定keep_name
        if ($model->hasAttribute($keep_name_attr)) {
            if ($model->getBool($keep_name_attr)) {
                $item_name_keep_original_weight += 2;
            } else {
                $item_name_keep_original_weight -= 2;
            }
        }
        $item_list = $model->getItemList();
        $item_arr = array();
        foreach ($item_list as $original_name => $item_schema) {
            $item_name = $this->fixItemName($original_name);
            //如果是define
            $schema_type = $item_schema->getType();
            if (Item::TYPE_DEFINE === $schema_type) {
                $item_schema = $this->convertDefineItem($item_schema);
            }
            $item = $this->makeItemObject($item_name, $item_schema);
            $item_weight = 0;
            //如果有在字段指定keep_name
            if ($item_schema->hasAttribute($keep_name_attr)) {
                if ($item_schema->getBool($keep_name_attr)) {
                    $item_weight = 3;
                } else {
                    $item_weight = -3;
                }
            }
            $item->setOriginalName($original_name);
            //如果权重大于0
            if ($item_name_keep_original_weight + $item_weight > 0) {
                $item->setKeepOriginalFlag(true);
            }
            $item_arr[$item_name] = $item;
        }
        $model_type = $model->getType();
        $extend_struct = null;
        $class_name = $this->fixClassName($model->getName());
        //继承关系
        $extend_struct_name = $model->getExtend();
        if ($extend_struct_name) {
            $extend_model = $this->getModel($extend_struct_name);
            if (null === $extend_model) {
                throw new Exception('无法 extend "' . $extend_struct_name . '"');
            }
            if (!isset($this->struct_list[$extend_struct_name])) {
                $this->parseStruct($extend_model);
            }
            $extend_struct = $this->struct_list[$extend_struct_name];
            $extend_type = $extend_struct->getType();
            //如果 继承不是来自 Struct, 那只能同类型继承
            if (Struct::TYPE_STRUCT !== $extend_type && $extend_type !== $model_type) {
                throw new Exception($class_name . ' can not extend ' . $extend_struct_name);
            }
        }
        //如果item为空
        if (empty($item_arr)) {
            //完全继承
            if ($extend_struct) {
                Exception::popStack();
                return $extend_struct;
            } //struct不允许空item
            elseif (Struct::TYPE_STRUCT === $model_type || Struct::TYPE_DATA === $model_type) {
                throw new Exception($class_name . ' is empty struct');
            }
        }
        //类名前缀
        $class_name_prefix = $this->build_opt->getConfig(Struct::getTypeName($model_type) . '_class_prefix');
        //类名后缀
        $class_name_suffix = $this->build_opt->getConfig(Struct::getTypeName($model_type) . '_class_suffix');
        //是否使用action name
        if ($this->is_keep_action_name && (!empty($class_name_prefix) || !empty($class_name_suffix))) {
            if (Model::TYPE_REQUEST === $model_type) {
                $class_name = str_replace('Request', '', $class_name);
            }
            if (Model::TYPE_RESPONSE === $model_type) {
                $class_name = str_replace('Response', '', $class_name);
            }
        }
        if (!empty($class_name_prefix)) {
            $struct_class_name = $this->joinName($class_name, Str::camelName($class_name_prefix));
        } elseif (!empty($class_name_suffix)) {
            $struct_class_name = $this->joinName(Str::camelName($class_name_suffix), $class_name);
        } else {
            $struct_class_name = Str::camelName($class_name);
        }
        $namespace = $model->getNameSpace();
        if (isset($this->name_stack[$namespace][$struct_class_name])) {
            throw new Exception('类名' . $namespace . '/' . $struct_class_name . '冲突');
        }
        $this->name_stack[$full_name][$struct_class_name] = true;
        $struct_obj = new Struct($namespace, $struct_class_name, $namespace, $model_type);
        //如果有注释
        if ($model->hasAttribute('note')) {
            $struct_obj->setNote($model->get('note'));
        }
        foreach ($item_arr as $name => $item) {
            $struct_obj->addItem($name, $item);
        }
        if ($extend_struct) {
            $struct_obj->extend($extend_struct);
        }
        $this->manager->addStruct($struct_obj);
        $struct_obj->setModelSchema($model);
        $this->struct_list[$full_name] = $struct_obj;
        $this->namespace_struct_list[$namespace][] = $struct_obj;
        unset($this->extend_stack[$full_name]);
        //如果 是request 生成uri
        if (Model::TYPE_REQUEST === $model_type) {
            $uri = $model->get('uri');
            if (empty($uri)) {
                $uri_prefix = $this->build_opt->getConfig('uri_prefix', 'ffan/v1');
                $action = $model->getAction();
                $uri = $uri_prefix . $namespace . '/' . $action->get('name');
            }
            $struct_obj->setUri($uri);
        }
        if ($model->hasAttribute('method')) {
            $struct_obj->setMethod(strtoupper($model->get('method')));
        }
        Exception::popStack();
        return $struct_obj;
    }

    /**
     * define item 转换
     * @param Item $item_schema
     * @return Item
     */
    private function convertDefineItem($item_schema)
    {
        $note = $item_schema->get('note');
        $plugin = $item_schema->getPlugin(File::PLUGIN_VALID);
        $name = $item_schema->get('name');
        $item_schema = $this->getDefine($item_schema->getUseDefine());
        //如果有自定义的note 或者 valid 或者 name
        if ($note || $plugin || $name) {
            $new_item_schema = clone $item_schema;
            if ($note) {
                $new_item_schema->set('note', $note);
            }
            if ($plugin) {
                $tmp_plugin = $new_item_schema->getPlugin(File::PLUGIN_VALID);
                if ($tmp_plugin) {
                    $tmp_plugin = clone $tmp_plugin;
                    $tmp_plugin->set('require', $plugin->get('require'));
                    $new_item_schema->addPlugin(File::PLUGIN_VALID, $tmp_plugin);
                } else {
                    $new_item_schema->addPlugin(File::PLUGIN_VALID, $plugin);
                }
            }
            if ($name) {
                $new_item_schema->set('name', $name);
            }
            $item_schema = $new_item_schema;
        }
        return $item_schema;
    }

    /**
     * @param string $name
     * @return string
     */
    private function fixClassName($name)
    {
        $name = str_replace('/', '_', $name);
        return Str::camelName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function fixItemName($name)
    {
        return Str::camelName($name, false);
    }

    /**
     * 生成item对象
     * @param string $name
     * @param Item $item_node 节点
     * @return ProtocolItem
     * @throws Exception
     */
    private function makeItemObject($name, $item_node)
    {
        Exception::pushStack('Parse Item ' . $item_node->getDoc());
        $type = $item_node->getType();
        switch ($type) {
            case ItemType::STRING:
                $item_obj = new StringItem($name, $this->manager);
                break;
            case ItemType::FLOAT:
                $item_obj = new FloatItem($name, $this->manager);
                break;
            case ItemType::BINARY:
                $item_obj = new BinaryItem($name, $this->manager);
                break;
            case ItemType::ARR:
                $item_obj = new ListItem($name, $this->manager);
                Exception::pushStack('Parse list item');
                $list_item = $this->parseList($name, $item_node);
                $item_obj->setItem($list_item);
                Exception::popStack();
                break;
            case ItemType::STRUCT:
                $item_obj = new StructItem($name, $this->manager);
                Exception::pushStack('Parse model item');
                $struct_obj = $this->parsePrivateStruct($item_node);
                $item_obj->setStruct($struct_obj);
                Exception::popStack();
                break;
            case ItemType::MAP:
                $item_obj = new MapItem($name, $this->manager);
                Exception::pushStack('Parse map item');
                $this->parseMap($name, $item_node, $item_obj);
                Exception::popStack();
                break;
            case ItemType::INT:
                $item_obj = new IntItem($name, $this->manager);
                $item_obj->setIntType($item_node->getNodeName());
                break;
            case ItemType::DOUBLE:
                $item_obj = new DoubleItem($name, $this->manager);
                break;
            case ItemType::BOOL:
                $item_obj = new BoolItem($name, $this->manager);
                break;
            default:
                throw new Exception('Unknown type');
        }
        //注释
        /** @var \DOMElement $item_node */
        if ($item_node->hasAttribute('note')) {
            $item_obj->setNote($item_node->get('note'));
        }
        //默认值
        if ($item_node->hasAttribute('default')) {
            $item_obj->setDefault($item_node->get('default'));
        }
        $this->parsePlugin($item_node, $item_obj);
        Exception::popStack();
        return $item_obj;
    }

    /**
     * 解析list
     * @param string $name
     * @param Item $item 节点
     * @return ProtocolItem
     * @throws Exception
     */
    private function parseList($name, Item $item)
    {
        $sub_item_list = $item->getSubItems();
        $sub_item = $sub_item_list[0];
        return $this->makeItemObject($name, $sub_item);
    }

    /**
     * 解析Map
     * @param string $name
     * @param Item $item 节点
     * @param MapItem $item_obj
     * @throws Exception
     */
    private function parseMap($name, $item, MapItem $item_obj)
    {
        $sub_item_list = $item->getSubItems();
        $key_item = $this->makeItemObject($name, $sub_item_list[0]);
        $value_item = $this->makeItemObject($name, $sub_item_list[1]);
        $item_obj->setKeyItem($key_item);
        $item_obj->setValueItem($value_item);
    }

    /**
     * 解析私有的struct
     * @param Item $item 节点
     * @return Struct
     * @throws Exception
     */
    private function parsePrivateStruct($item)
    {
        $sub_model_name = $item->getSubModelName();
        $sub_model = $this->getModel($sub_model_name);
        if (null === $sub_model) {
            throw new Exception('没有找到继承的model:' . $sub_model_name);
        }
        //如果是引用其它Struct，加载其它Struct
        $struct = $this->parseStruct($sub_model);
        return $struct;
    }

    /**
     * 解析trigger
     * @param Plugin $plugin_node
     * @param ProtocolItem $item
     * @throws Exception
     */
    private function parseTrigger($plugin_node, $item)
    {
        $type = $plugin_node->get('type');
        switch ($type) {
            case 'buf':
                $trigger = new BufTrigger();
                break;
            default:
                throw new Exception('Unknown trigger:' . $type);
        }
        $trigger->init($plugin_node);
        $item->addTrigger($trigger);
    }


    /**
     * 插件解析
     * @param Item $dom_node 节点
     * @param ProtocolItem $item
     */
    private function parsePlugin($dom_node, $item)
    {
        $plugin_list = $dom_node->getPluginList();
        if (!$plugin_list) {
            return;
        }
        foreach ($plugin_list as $plugin_name => $plugin_node) {
            //如果是触发器，特殊处理
            if ('trigger' === $plugin_name) {
                $this->parseTrigger($plugin_node, $item);
            } else {
                $plugin = $this->manager->getPlugin($plugin_name);
                if (!$plugin) {
                    continue;
                }
                $plugin->init($this, $plugin_node, $item);
            }
        }
    }

    /**
     * 判断名称是否可用
     * @param string $name 类名
     * @param string $prefix 前缀
     * @return string
     * @throws Exception
     */
    private function joinName($name, $prefix = '')
    {
        if (!empty($prefix)) {
            $name = $prefix . $name;
        }
        return Str::camelName($name, true);
    }

    /**
     * 按namespace获取model list
     * @param string $namespace
     * @return Struct[]
     */
    public function getStructByNameSpace($namespace)
    {
        if (!isset($this->namespace_struct_list[$namespace])) {
            return [];
        }
        return $this->namespace_struct_list[$namespace];
    }

    /**
     * 获取所有的struct
     * @return Struct[]
     */
    public function getAllStruct()
    {
        return $this->struct_list;
    }

    /**
     * 获取struct
     * @param string $name
     * @return Struct|null
     */
    public function getStruct($name)
    {
        return isset($this->struct_list[$name]) ? $this->struct_list[$name] : null;
    }

    /**
     * 记录文件之间的依赖关系
     * @param string $namespace
     * @param string $require_namespace
     */
    public function setFileRequire($namespace, $require_namespace)
    {
        $this->file_require_arr[$namespace][] = $require_namespace;
    }

    /**
     * 加入define
     * @param string $namespace
     * @param ProtocolItem[] $item_arr
     */
    public function addDefine($namespace, $item_arr)
    {
        $this->define_list[$namespace] = $item_arr;
    }

    /**
     * 获取define
     * @param string $use_define
     * @return Item
     * @throws Exception
     */
    public function getDefine($use_define)
    {
        $namespace = dirname($use_define);
        $name = basename($use_define);
        if (!isset($this->define_list[$namespace][$name])) {
            throw new Exception('define `' . $name . '`not found in ' . $namespace);
        }
        return $this->define_list[$namespace][$name];
    }

}
