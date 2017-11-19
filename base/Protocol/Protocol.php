<?php

namespace FFan\Dop\Protocol;

use FFan\Dop\Build\BufTrigger;
use FFan\Dop\Build\BuildOption;
use FFan\Dop\Build\NodeBase;
use FFan\Dop\Build\Shader;
use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Dop\Schema\File;
use FFan\Dop\Schema\Model;
use FFan\Std\Common\Str as FFanStr;
use \FFan\Dop\Schema\Item as SchemaItem;
use \FFan\Dop\Schema\File as SchemaFile;

/**
 * Class Protocol
 * @package ffan
 */
class Protocol
{
    /**
     * 请求节点
     */
    const REQUEST_NODE = 'request';

    /**
     * 返回节点
     */
    const RESPONSE_NODE = 'response';

    /**
     * 解析步骤：struct
     */
    const QUERY_STEP_STRUCT = 1;

    /**
     * 解析步骤：action
     */
    const QUERY_STEP_ACTION = 2;

    /**
     * 解析步骤：data
     */
    const QUERY_STEP_DATA = 4;

    /**
     * 解析步骤：shader
     */
    const QUERY_STEP_SHADER = 8;

    /**
     * @var string 文件名
     */
    private $file_name;

    /**
     * @var array 防止同名文件冲突的数组
     */
    private $name_stack = array();

    /**
     * @var string 命名空间
     */
    private $namespace;

    /**
     * @var string 协议文件名
     */
    private $xml_file;

    /**
     * @var array 允许的方法
     */
    //private static $http_method_list = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var int 已经解析的步骤
     */
    private $query_step = 0;

    /**
     * @var BuildOption build_opt
     */
    private $build_opt;

    /**
     * @var File scheme
     */
    private $scheme_file;

    /**
     * @var Struct[]
     */
    private static $all_struct;

    /**
     * ProtocolScheme constructor.
     * @param Manager $manager
     * @param string $schema_file 命名空间
     * @throws Exception
     */
    public function __construct(Manager $manager, $schema_file)
    {
        $this->namespace = File::fileToNamespace($schema_file);
        $this->xml_file = $schema_file;
        $this->manager = $manager;
        $this->scheme_file = $manager->getScheme($schema_file);
        if (null === $this->scheme_file) {
            throw new Exception('Can not found scheme :' . $schema_file);
        }
        $this->build_opt = $manager->getCurrentBuildOpt();
        $this->parse();
    }

    /**
     * 解析该xml文件
     */
    public function parse()
    {
        $this->queryStruct();
        $this->queryAction();
        $this->queryData();
        $this->queryShader();
    }

    /**
     * 解析公用struct
     */
    public function queryStruct()
    {
        //已经解析过了，就打标志，避免重复解析
        if ($this->query_step & self::QUERY_STEP_STRUCT) {
            return;
        }
        $this->query_step |= self::QUERY_STEP_STRUCT;
        $node_list = $this->scheme_file->getModels(Model::TYPE_STRUCT);
        //所有struct
        $all_struct = array();
        //顺序
        $extend_index = array();
        foreach($node_list as $name => $model) {
            $name = FFanStr::camelName($name);
            $ext_name = $model->getExtend();
            //如果有继承, 并且就是同一个文件里的继承
            if (null !== $ext_name && FFanStr::isValidVarName($ext_name)) {
                $ext_name = FFanStr::camelName($ext_name);
                $extend_index[$ext_name] = true;
            }
            $all_struct[$name] = $model;
        }
        //先把依赖的先加载完
        foreach ($extend_index as $name => $v) {
            if (!isset($all_struct[$name])) {
                continue;
            }
            $this->parseStruct($name, $all_struct[$name], Struct::TYPE_STRUCT);
            unset($all_struct[$name]);
        }
        //再处理其它的struct
        foreach ($all_struct as $name => $struct) {
            $this->parseStruct($name, $struct, Struct::TYPE_STRUCT);
        }
    }

    /**
     * 解析Action协议
     */
    private function queryAction()
    {
        //已经解析过了，就打标志，避免重复解析
        if ($this->query_step & self::QUERY_STEP_ACTION) {
            return;
        }
        $this->query_step |= self::QUERY_STEP_ACTION;
        $request_model = $this->scheme_file->getModels(Model::TYPE_REQUEST);
        foreach ($request_model as $name => $model) {
            $tmp_name = FFanStr::camelName($name);
            $this->parseStruct($tmp_name, $model, Struct::TYPE_REQUEST);
        }

        $response_model = $this->scheme_file->getModels(Model::TYPE_REQUEST);
        foreach ($response_model as $name => $model) {
            $tmp_name = FFanStr::camelName($name);
            $this->parseStruct($tmp_name, $model, Struct::TYPE_REQUEST);
        }
    }

    /**
     * 解析Data协议
     */
    private function queryData()
    {
        //已经解析过了，就打标志，避免重复解析
        if ($this->query_step & self::QUERY_STEP_DATA) {
            return;
        }
        $this->query_step |= self::QUERY_STEP_DATA;
        $node_list = $this->scheme_file->getModels(Model::TYPE_DATA);
        foreach ($node_list as $name => $model) {
            $name = FFanStr::camelName($name);
            $this->parseStruct($name, $model, Struct::TYPE_DATA);
        }
    }

    /**
     * 解析render
     */
    private function queryShader()
    {
        //已经解析过了，就打标志，避免重复解析
        if ($this->query_step & self::QUERY_STEP_SHADER) {
            return;
        }
        $this->query_step |= self::QUERY_STEP_SHADER;
        $shader_list = $this->scheme_file->getShaderList();
        if (!$shader_list) {
            return;
        }
        foreach ($shader_list as $shader_node) {
            $shader = new Shader($this->manager, $shader_node);
            $this->manager->addShader($shader);
        }
    }

    /**
     * 修正字段名
     * @param string $item_name
     * @return string
     */
    public function fixItemName($item_name)
    {
        return FFanStr::camelName($item_name, false);
    }

    /**
     * 解析struct
     * @param string $class_name 上级类名
     * @param Model $model
     * @param int $type 类型
     * @return Struct
     * @throws Exception
     */
    private function parseStruct($class_name, $model, $type = Struct::TYPE_STRUCT)
    {
        $full_name = $this->namespace .'/'. $model->getName();
        if (isset(self::$all_struct[$full_name])) {
            return self::$all_struct[$full_name];
        }
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
        $extend_struct = null;
        //继承关系
        $extend_struct_name = $model->getExtend();
        if ($extend_struct_name) {
            $extend_struct_name = $this->getFullName($extend_struct_name);
            $conf_suffix = $this->build_opt->getConfig('struct_class_suffix');
            if (!empty($conf_suffix)) {
                $extend_struct_name .= FFanStr::camelName($conf_suffix);
            }
            $extend_struct = $this->manager->loadRequireStruct($extend_struct_name, $this->xml_file);
            if (null === $extend_struct) {
                throw new Exception('无法 extend "' . $extend_struct_name .'"');
            }
            $extend_type = $extend_struct->getType();
            //如果 继承不是来自 Struct, 那只能同类型继承
            if (Struct::TYPE_STRUCT !== $extend_struct && $extend_type !== $type) {
                throw new Exception($class_name .' can not extend '. $extend_struct_name);
            }
        }
        //如果item为空
        if (empty($item_arr)) {
            //完全继承
            if ($extend_struct) {
                return $extend_struct;
            } //struct不允许空item
            elseif (Struct::TYPE_STRUCT === $type || Struct::TYPE_DATA === $type) {
                throw new Exception($class_name .' is empty struct');
            }
        }
        $class_name_prefix = $this->build_opt->getConfig(Struct::getTypeName($type) .'_class_prefix');
        if (!empty($class_name_prefix)) {
            $struct_class_name = $this->joinName($class_name, FFanStr::camelName($class_name_prefix));
        } else {
            $class_name_suffix = $this->build_opt->getConfig(Struct::getTypeName($type) .'_class_suffix');
            $struct_class_name = $this->joinName(FFanStr::camelName($class_name_suffix), $class_name);
        }
        $struct_obj = new Struct($this->namespace, $struct_class_name, $this->xml_file, $type);
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
        self::$all_struct[$full_name] = $struct_obj;
        $struct_obj->setModelSchema($model);
        return $struct_obj;
    }

    /**
     * 生成item对象
     * @param string $name
     * @param SchemaItem $dom_node 节点
     * @return Item
     * @throws Exception
     */
    private function makeItemObject($name, $dom_node)
    {
        $type = $dom_node->getType();
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
                $list_item = $this->parseList($name, $dom_node);
                $item_obj->setItem($list_item);
                break;
            case ItemType::STRUCT:
                $item_obj = new StructItem($name, $this->manager);
                $struct_obj = $this->parsePrivateStruct($name, $dom_node);
                $item_obj->setStruct($struct_obj);
                break;
            case ItemType::MAP:
                $item_obj = new MapItem($name, $this->manager);
                $this->parseMap($name, $dom_node, $item_obj);
                break;
            case ItemType::INT:
                $item_obj = new IntItem($name, $this->manager);
                $item_obj->setIntType($dom_node->getNodeName());
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
        /** @var \DOMElement $dom_node */
        if ($dom_node->hasAttribute('note')) {
            $item_obj->setNote($dom_node->get('note'));
        }
        //默认值
        if ($dom_node->hasAttribute('default')) {
            $item_obj->setDefault($dom_node->get('default'));
        }
        $this->parsePlugin($dom_node, $item_obj);
        return $item_obj;
    }

    /**
     * 插件解析
     * @param SchemaItem $dom_node 节点
     * @param Item $item
     */
    private function parsePlugin($dom_node, $item)
    {
        $plugin_list = $dom_node->getPluginList();
        if (!$plugin_list) {
            return;
        }
        foreach ($plugin_list as $plugin_name => $plugin) {
            //如果是触发器，特殊处理
            if ('trigger' === $plugin_name) {
                //$this->parseTrigger($dom_node, $item);
            } else {
                $plugin = $this->manager->getPlugin($plugin_name);
                if (!$plugin) {
                    continue;
                }
                //$plugin->init($this, $tmp_node, $item);
            }
        }
    }

    /**
     * 解析trigger
     * @param \DOMElement $dom_node
     * @param Item $item
     * @throws Exception
     */
    private function parseTrigger($dom_node, $item)
    {
        $type = NodeBase::read($dom_node, 'type');
        switch ($type) {
            case 'buf':
                $trigger = new BufTrigger();
                break;
            default:
                throw new Exception('Unknown trigger:'. $type);
        }
        $trigger->init($dom_node);
        $item->addTrigger($trigger);
    }

    /**
     * 解析list
     * @param string $name
     * @param SchemaItem $item 节点
     * @return Item
     * @throws Exception
     */
    private function parseList($name, SchemaItem $item)
    {
        $sub_item_list = $item->getSubItems();
        $sub_item = $sub_item_list[0];
        return $this->makeItemObject($name, $sub_item);
    }

    /**
     * 解析Map
     * @param string $name
     * @param SchemaItem $item 节点
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
        if (isset($this->name_stack[$name])) {
            throw new Exception('Name:' . $name . ' 已经存在');
        }
        $this->name_stack[$name] = true;
        $this->checkName($name);
        return FFanStr::camelName($name, true);
    }

    /**
     * 检查name是否可以做类名
     * @param string $name
     * @throws Exception
     */
    private function checkName($name)
    {
        if (empty($name) || 0 === preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) > 0) {
            throw new Exception('Name:' . $name . ' is invalid');
        }
    }

    /**
     * 获取类名全路径
     * @param string $struct_name
     * @return string
     * @throws Exception
     */
    public function getFullName($struct_name)
    {
        if (empty($struct_name)) {
            throw new Exception('struct name error');
        }
        //名称不合法
        if (!preg_match('/^\/?[a-zA-Z_][a-zA-Z_\d]*(\/[a-zA-Z_][a-zA-Z_\d]*)*$/', $struct_name)) {
            throw new Exception('Invalid struct name:' . $struct_name);
        }
        $class_name = FFanStr::camelName(basename($struct_name));
        $dir_name = dirname($struct_name);
        //没有目录
        if ('.' === $dir_name) {
            return $this->namespace . '/' . $class_name;
        }
        //补全
        if ('/' !== $struct_name[0]) {
            $dir_name = $this->namespace . '/' . $dir_name;
        }
        return $dir_name . '/' . $class_name;
    }

    /**
     * 解析私有的struct
     * @param string $name
     * @param SchemaItem $item 节点
     * @return Struct
     */
    private function parsePrivateStruct($name, SchemaItem $item)
    {
        $sub_model_name = $item->getSubModelName();
        //如果是引用其它Struct，加载其它Struct
        $struct = $this->parseStruct($sub_model_name, SchemaFile::getModelSchema($sub_model_name));
        return $struct;
    }
}
