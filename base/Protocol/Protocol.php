<?php

namespace FFan\Dop\Protocol;

use FFan\Dop\Build\BufTrigger;
use FFan\Dop\Build\BuildOption;
use FFan\Dop\Build\NodeBase;
use FFan\Dop\Build\Shader;
use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Std\Common\Str as FFanStr;
use FFan\Std\Common\Utils as FFanUtils;

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
     * @var \DOMDocument xml_handle
     */
    private $xml_handle;

    /**
     * @var \DOMXpath
     */
    private $path_handle;

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
     * @var array 允许的方法
     */
    private static $allow_method_list = array('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'PATCH');

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var int 已经解析的步骤
     */
    private $query_step = 0;

    /**
     * @var string xml文件名称
     */
    private $xml_file_name;

    /**
     * @var BuildOption build_opt
     */
    private $build_opt;

    /**
     * ProtocolXml constructor.
     * @param Manager $manager
     * @param string $file_name 协议文件
     * @throws Exception
     */
    public function __construct(Manager $manager, $file_name)
    {
        $base_path = $manager->getBasePath();
        $this->xml_file_name = $file_name;
        $full_name = FFanUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new Exception('找不到协议文件:' . $full_name);
        }
        $this->file_name = $full_name;
        Exception::setAppendMsg('Parse xml ' . $full_name);
        $this->xml_handle = new \DOMDocument();
        $this->xml_handle->load($full_name);
        $dir_name = dirname($file_name);
        if ('.' === $dir_name) {
            $dir_name = DIRECTORY_SEPARATOR;
        }
        //如果不是以/开始，前端加 /
        if (DIRECTORY_SEPARATOR !== $dir_name[0]) {
            $dir_name = DIRECTORY_SEPARATOR . $dir_name;
        }
        //如果不是以 / 结尾，后面加 /
        if (DIRECTORY_SEPARATOR !== $dir_name[strlen($dir_name) - 1]) {
            $dir_name .= DIRECTORY_SEPARATOR;
        }
        $this->namespace = $dir_name . basename($file_name, '.xml');
        $this->manager = $manager;
        $this->build_opt = $manager->getCurrentBuildOpt();
    }

    /**
     * 获取Xpath
     * @return \DOMXpath
     */
    private function getPathHandle()
    {
        if (null === $this->path_handle) {
            $this->path_handle = new \DOMXpath($this->xml_handle);
        }
        return $this->path_handle;
    }

    /**
     * 解析该xml文件
     */
    public function query()
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
        $this->queryModel('struct');
        $this->queryModel('model');
    }

    /**
     * 解析model
     * @param string $tag_name
     * @throws Exception
     */
    private function queryModel($tag_name)
    {
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/' . $tag_name);
        if (null === $node_list) {
            return;
        }
        //所有struct
        $all_struct = array();
        //顺序
        $extend_index = array();
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->setLineNumber($struct->getLineNo());
            if (!$struct->hasAttribute('name')) {
                throw new Exception('Struct must have name attribute');
            }
            $name = trim($struct->getAttribute('name'));
            $name = FFanStr::camelName($name);
            //将node还原成xml代码
            $xml_string = $struct->C14N();
            //如果有本文件继承
            if (false !== strpos($xml_string, ' extend=') && preg_match_all('/extend=[\'"]([a-zA-Z_\d]+)[\'"]/', $xml_string, $extend_arr)) {
                //整理顺序，保证不管怎么写，都能解析，不然就必须把依赖的写在前面
                foreach ($extend_arr[1] as $ext_name) {
                    $ext_name = FFanStr::camelName($ext_name);
                    $extend_index[$ext_name] = true;
                }
            }
            $all_struct[$name] = $struct;
        }
        //先把依赖的先加载完
        foreach ($extend_index as $name => $v) {
            if (!isset($all_struct[$name])) {
                continue;
            }
            $this->parseStruct($name, $all_struct[$name], true, Struct::TYPE_STRUCT);
            unset($all_struct[$name]);
        }
        //再处理其它的struct
        foreach ($all_struct as $name => $struct) {
            $this->parseStruct($name, $struct, true, Struct::TYPE_STRUCT);
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
        $path_handle = $this->getPathHandle();
        $action_list = $path_handle->query('/protocol/action');
        if (null === $action_list) {
            return;
        }
        for ($i = 0; $i < $action_list->length; ++$i) {
            /** @var \DOMElement $action */
            $action = $action_list->item($i);
            $this->setLineNumber($action->getLineNo());
            if (!$action->hasAttribute('name')) {
                throw new Exception('Action must have name attribute');
            }
            //action 的name支持 /aa/bb 的格式
            $name = $action->getAttribute('name');
            $tmp_name = str_replace('/', '_', trim($name));
            if ($tmp_name !== $name) {
                $action->setAttribute('name', $tmp_name);
            }
            $tmp_name = FFanStr::camelName($tmp_name);
            $this->parseAction($tmp_name, $action);
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
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/data');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $data_node */
            $data_node = $node_list->item($i);
            $this->setLineNumber($data_node->getLineNo());
            if (!$data_node->hasAttribute('name')) {
                throw new Exception('Data must have name attribute');
            }
            $name = trim($data_node->getAttribute('name'));
            $name = FFanStr::camelName($name, true);
            $extra_packer = $this->parseExtraPacer($data_node);
            $is_public = true === (bool)$data_node->getAttribute('public');
            $struct = $this->parseStruct($name, $data_node, $is_public, Struct::TYPE_DATA);
            $this->addExtraPacker($extra_packer, $struct);
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
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/shader');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $shader_node */
            $shader_node = $node_list->item($i);
            $this->setLineNumber($shader_node->getLineNo());
            $shader = new Shader($this->manager, $shader_node);
            $this->manager->addShader($shader);
        }
    }

    /**
     * 解析extra-packer设置
     * @param \DOMElement $node
     * @return array|null
     */
    private function parseExtraPacer(\DOMElement $node)
    {
        $extra_packer = $node->getAttribute('packer-extra');
        //附加packer
        if (!empty($extra_packer)) {
            $extra_packer = FFanStr::split($extra_packer);
        }
        return $extra_packer;
    }

    /**
     * 给struct附加extra packer
     * @param array $extra_packer
     * @param Struct $struct
     */
    private function addExtraPacker($extra_packer, $struct)
    {
        if (empty($extra_packer)) {
            return;
        }
        foreach ($extra_packer as $name) {
            $struct->addExtraPacker($name);
        }
    }

    /**
     * 解析request
     * @param $action_name
     * @param \DOMElement $action
     * @throws Exception
     */
    private function parseAction($action_name, \DOMElement $action)
    {
        $node_list = $action->childNodes;
        $request_count = 0;
        $response_count = 0;
        $note_info = $action->getAttribute('note');
        $action_method = $action->getAttribute('method');
        if (empty($action_method)) {
            $action_method = 'get';
        }
        $extra_packer = $this->parseExtraPacer($action);
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            $this->setLineNumber($node->getLineNo());
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $class_name = $action_name;
            $node_name = strtolower($node->nodeName);
            $method = '';
            if (self::REQUEST_NODE === $node_name) {
                if (++$request_count > 1) {
                    throw new Exception('Only one request node allowed');
                }
                $method = $node->getAttribute('method');
                //如果在 request 上没有指定method, 尝试使用 action 上的method
                if (empty($method)) {
                    $method = $action_method;
                }
                $node->setAttribute('method', $method);
                $type = Struct::TYPE_REQUEST;
                //$node_name = $build_opt->getConfig('request_class_suffix', 'request');
            } elseif (self::RESPONSE_NODE === $node_name) {
                if (++$response_count > 1) {
                    throw new Exception('Only one response node allowed');
                }
                $this->responseModelClassName($node, $action_name);
                $type = Struct::TYPE_RESPONSE;
            } else {
                throw new Exception('Unknown node:' . $node_name);
            }
            $name = FFanStr::camelName($class_name);
            /** @var \DOMElement $node */
            $struct = $this->parseStruct($name, $node, false, $type);
            $struct->setNode($node);
            $node_str = '';
            if ($note_info) {
                $node_str .= $note_info;
            }
            $struct->setNote($node_str);
            //如果 是request
            if (Struct::TYPE_REQUEST === $type) {
                $method = strtoupper($method);
                if (!in_array($method, self::$allow_method_list)) {
                    throw new Exception('不支持的method:' . $method);
                }
                $struct->setMethod($method);
                if ($node->hasAttribute('uri')) {
                    $struct->setUri($node->getAttribute('uri'));
                }
            }
            $this->addExtraPacker($extra_packer, $struct);
        }
    }

    /**
     * 自动给response下面的model生成class_name
     * @param \DOMDocument $struct_node
     * @param string $action_name
     */
    private function responseModelClassName($struct_node, $action_name)
    {
        $node_list = $struct_node->childNodes;
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $node_name = $node->nodeName;
            if (!$this->isStruct($node_name)) {
                continue;
            }
            if ($node->getAttribute('class_name')) {
                continue;
            }
            $name = $node->getAttribute('name');
            if (!$name) {
                continue;
            }
            $node->setAttribute('class_name', $action_name .'_'. $name);
        }
    }

    /**
     * 是否为struct
     * @param string $name
     * @return bool
     */
    private function isStruct($name)
    {
        return 'model' === $name || 'struct' === $name;
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
     * @param \DomElement $struct_node
     * @param bool $is_public 是否可以被extend
     * @param int $type 类型
     * @return Struct
     * @throws Exception
     */
    private function parseStruct($class_name, \DomElement $struct_node, $is_public = false, $type = Struct::TYPE_STRUCT)
    {
        Manager::setCurrentStruct($struct_node);
        $keep_name_attr = 'keep_name';
        //保持 原始字段 命名的权重
        $item_name_keep_original_weight = (int)$this->build_opt->isKeepOriginalName();
        $node_list = $struct_node->childNodes;
        //如果有在struct指定keep_name
        if ($struct_node->hasAttribute($keep_name_attr)) {
            if ((bool)$struct_node->getAttribute($keep_name_attr)) {
                $item_name_keep_original_weight += 2;
            } else {
                $item_name_keep_original_weight -= 2;
            }
        }
        $item_arr = array();
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            //插件
            if ($this->isPluginNode($node)) {
                continue;
            }
            $this->setLineNumber($node->getLineNo());
            /** @var \DOMElement $node */
            if (!$node->hasAttribute('name')) {
                //如果 是struct 并且指定了 extend, 就不需要名字
                if ($this->isStruct($node->tagName) && $node->hasAttribute('extend')) {
                    $extend = basename($node->getAttribute('extend'));
                    $node->setAttribute('name', $extend);
                } else {
                    throw new Exception('Attribute `name` required!');
                }
            }
            $original_name = trim($node->getAttribute('name'));
            $this->checkName($original_name);
            $item_name = $this->fixItemName($original_name);
            $item = $this->makeItemObject($item_name, $node);
            if (isset($item_arr[$item_name])) {
                throw new Exception('Item name:' . $item_name . ' 已经存在');
            }
            $item_weight = 0;
            //如果有在字段指定keep_name
            if ($node->hasAttribute($keep_name_attr)) {
                if ((bool)$node->getAttribute($keep_name_attr)) {
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
        if ($struct_node->hasAttribute('extend')) {
            $struct_name = trim($struct_node->getAttribute('extend'));
            if (empty($struct_name)) {
                throw new Exception('extend 不能为空');
            }
            $struct_name = $this->getFullName($struct_name);
            $conf_suffix = $this->build_opt->getConfig('struct_class_suffix');
            if (!empty($conf_suffix)) {
                $struct_name .= FFanStr::camelName($conf_suffix);
            }
            $extend_struct = $this->manager->loadRequireStruct($struct_name, $this->xml_file_name);
            if (null === $extend_struct) {
                throw new Exception('无法 extend "' . $struct_name . '"');
            } elseif (!$extend_struct->isPublic() && $this->namespace !== $extend_struct->getNamespace()) {
                throw new Exception('struct:' . $struct_name . ' is not public!');
            }
        }
        //如果item为空
        if (empty($item_arr)) {
            //完全继承
            if ($extend_struct) {
                return $extend_struct;
            } //struct不允许空item
            elseif (Struct::TYPE_STRUCT === $type || Struct::TYPE_DATA === $type) {
                throw new Exception('Empty struct');
            }
        }
        $class_name_prefix = $this->build_opt->getConfig(Struct::getTypeName($type) . '_class_prefix');
        if (!empty($class_name_prefix)) {
            $struct_class_name = $this->joinName($class_name, FFanStr::camelName($class_name_prefix));
        } else {
            $class_name_suffix = $this->build_opt->getConfig(Struct::getTypeName($type) . '_class_suffix');
            $struct_class_name = $this->joinName(FFanStr::camelName($class_name_suffix), $class_name);
        }
        $struct_obj = new Struct($this->namespace, $struct_class_name, $this->xml_file_name, $type, $is_public);
        //如果有注释
        if ($struct_node->hasAttribute('note')) {
            $struct_obj->setNote($struct_node->getAttribute('note'));
        }
        foreach ($item_arr as $name => $item) {
            $struct_obj->addItem($name, $item);
        }
        if ($extend_struct) {
            $struct_obj->extend($extend_struct);
        }
        $this->manager->addStruct($struct_obj);
        Manager::setCurrentStruct(null);
        return $struct_obj;
    }

    /**
     * 生成item对象
     * @param string $name
     * @param \DOMElement $dom_node 节点
     * @return Item
     * @throws Exception
     */
    private function makeItemObject($name, $dom_node)
    {
        $type = ItemType::getType($dom_node->nodeName);
        if (null === $type) {
            throw new Exception('Unknown type `' . $dom_node->nodeName . '`');
        }
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
                $item_obj->setIntType($dom_node->nodeName);
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
            $note = trim($dom_node->getAttribute('note'));
            $item_obj->setNote($note);
        }
        //默认值
        if ($dom_node->hasAttribute('default')) {
            $default = $dom_node->getAttribute('default');
            $item_obj->setDefault($default);
        }
        $this->parsePlugin($dom_node, $item_obj);
        return $item_obj;
    }

    /**
     * 插件解析
     * @param \DOMElement $dom_node 节点
     * @param Item $item
     */
    private function parsePlugin($dom_node, $item)
    {
        $item_list = $dom_node->childNodes;
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
            if (XML_ELEMENT_NODE !== $tmp_node->nodeType) {
                continue;
            }
            if (!$this->isPluginNode($tmp_node)) {
                continue;
            }
            $this->setLineNumber($tmp_node->getLineNo());
            $plugin_name = substr($tmp_node->nodeName, strlen('plugin_'));
            //如果是触发器，特殊处理
            if ('trigger' === $plugin_name) {
                $this->parseTrigger($tmp_node, $item);
            } else {
                $plugin = $this->manager->getPlugin($plugin_name);
                if (!$plugin) {
                    continue;
                }
                $plugin->init($this, $tmp_node, $item);
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
                throw new Exception('Unknown trigger:' . $type);
        }
        $trigger->init($dom_node);
        $item->addTrigger($trigger);
    }

    /**
     * 是否是插件节点
     * @param \DOMNode $node
     * @return bool
     */
    private function isPluginNode($node)
    {
        return 0 === strpos($node->nodeName, 'plugin_');
    }

    /**
     * 解析list
     * @param string $name
     * @param \DOMNode $item 节点
     * @return Item
     * @throws Exception
     */
    private function parseList($name, \DOMNode $item)
    {
        $item_list = $item->childNodes;
        $type_node = null;
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
            $this->setLineNumber($tmp_node->getLineNo());
            if (XML_ELEMENT_NODE !== $tmp_node->nodeType) {
                continue;
            }
            if ($this->isPluginNode($tmp_node)) {
                continue;
            }
            if (null !== $type_node) {
                throw new Exception('List只能有一个节点');
            }
            $type_node = $tmp_node;
        }
        if (null === $type_node) {
            throw new Exception('List下必须包括一个指定list类型的节点');
        }
        if ($type_node->hasAttribute('name')) {
            $tmp_name = trim($type_node->getAttribute('name'));
            if (!empty($tmp_name)) {
                $this->checkName($tmp_name);
                $name = FFanStr::camelName($tmp_name);
            }
        }
        return $this->makeItemObject($name, $type_node);
    }

    /**
     * 解析Map
     * @param string $name
     * @param \DOMNode $item 节点
     * @param MapItem $item_obj
     * @throws Exception
     */
    private function parseMap($name, \DOMNode $item, MapItem $item_obj)
    {
        $item_list = $item->childNodes;
        $key_node = null;
        $value_node = null;
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
            $this->setLineNumber($tmp_node->getLineNo());
            if (XML_ELEMENT_NODE !== $tmp_node->nodeType) {
                continue;
            }
            if ($this->isPluginNode($tmp_node)) {
                continue;
            }
            if (null === $key_node) {
                $key_node = $tmp_node;
                $key_item = $this->makeItemObject($name, $key_node);
                $item_obj->setKeyItem($key_item);
            } elseif (null === $value_node) {
                $value_node = $tmp_node;
                if ($tmp_node->hasAttribute('name')) {
                    $tmp_name = trim($tmp_node->getAttribute('name'));
                    if (!empty($tmp_name)) {
                        $this->checkName($tmp_name);
                        $name = FFanStr::camelName($tmp_name);
                    }
                }
                $value_item = $this->makeItemObject($name, $value_node);
                $item_obj->setValueItem($value_item);
            } else {
                throw new Exception('Map下只能有两个节点');
            }
            $key_node = $tmp_node;
        }
        if (null === $key_node || null === $value_node) {
            throw new Exception('Map下必须包含两个节点');
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
     * @param \DOMElement $item 节点
     * @return Struct
     */
    private function parsePrivateStruct($name, \DOMElement $item)
    {
        if ($item->hasAttribute('class_name')) {
            $class_name = trim($item->getAttribute('class_name'));
            if (!empty($class_name)) {
                $name = FFanStr::camelName($class_name);
            }
        }
        //如果是引用其它Struct，加载其它Struct
        $struct = $this->parseStruct($name, $item, false);
        return $struct;
    }

    /**
     * 设置行号
     * @param string $line_number
     */
    private function setLineNumber($line_number)
    {
        $position_info = 'File:' . $this->file_name . ' Line:' . $line_number;
        Exception::setAppendMsg($position_info);
    }
}
