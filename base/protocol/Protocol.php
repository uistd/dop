<?php

namespace ffan\dop\protocol;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\php\utils\Str as FFanStr;
use ffan\php\utils\Utils as FFanUtils;

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
     * @var string xml文件名称
     */
    private $xml_file_name;

    /**
     * @var int 当前正在解析的struct类型
     */
    private $current_struct_type;

    /**
     * ProtocolXml constructor.
     * @param Manager $manager
     * @param string $file_name 协议文件
     */
    public function __construct(Manager $manager, $file_name)
    {
        $base_path = $manager->getBasePath();
        $this->xml_file_name = $file_name;
        $full_name = FFanUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new \InvalidArgumentException('Can not open xml:' . $full_name);
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
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/struct');
        if (null === $node_list) {
            return;
        }
        $this->current_struct_type = Struct::TYPE_STRUCT;
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->setLineNumber($struct->getLineNo());
            if (!$struct->hasAttribute('name')) {
                throw new Exception('Struct must have name attribute');
            }
            $name = trim($struct->getAttribute('name'));
            $name = FFanStr::camelName($name, true);
            //默认是公共的struct
            $this->parseStruct($name, $struct, true, Struct::TYPE_STRUCT, false);
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
        $this->current_struct_type = Struct::TYPE_DATA;
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->setLineNumber($struct->getLineNo());
            if (!$struct->hasAttribute('name')) {
                throw new Exception('Data must have name attribute');
            }
            $name = trim($struct->getAttribute('name'));
            $name = FFanStr::camelName($name, true);
            $is_public = true === (bool)$struct->getAttribute('public');
            $this->parseStruct($name, $struct, $is_public, Struct::TYPE_DATA, false);
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
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            $this->setLineNumber($node->getLineNo());
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $class_name = $action_name;
            $node_name = strtolower($node->nodeName);
            if (self::REQUEST_NODE === $node_name) {
                if (++$request_count > 1) {
                    throw new Exception('Only one request node allowed');
                }
                $type = Struct::TYPE_REQUEST;
                //$node_name = $build_opt->getConfig('request_class_suffix', 'request');
            } elseif (self::RESPONSE_NODE === $node_name) {
                if (++$response_count > 1) {
                    throw new Exception('Only one response node allowed');
                }
                $type = Struct::TYPE_RESPONSE;
            } else {
                throw new Exception('Unknown node:' . $node_name);
            }
            $this->current_struct_type = $type;
            $name = FFanStr::camelName($class_name);
            /** @var \DOMElement $node */
            $struct = $this->parseStruct($name, $node, false, $type);
            $struct->addReferType($type);
        }
    }

    /**
     * 解析struct
     * @param string $class_name 上级类名
     * @param \DomElement $struct
     * @param bool $is_public 是否可以被extend
     * @param int $type 类型
     * @param bool $allow_extend 是否允许extend其它struct
     * @return Struct
     * @throws Exception
     */
    private function parseStruct($class_name, \DomElement $struct, $is_public = false, $type = Struct::TYPE_STRUCT, $allow_extend = true)
    {
        $node_list = $struct->childNodes;
        $item_arr = array();
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $this->setLineNumber($node->getLineNo());
            /** @var \DOMElement $node */
            if (!$node->hasAttribute('name')) {
                //如果 是struct 并且指定了 extend, 就不需要名字
                if ('struct' === $node->tagName && $node->hasAttribute('extend')) {
                    $node->setAttribute('name',$node->getAttribute('extend'));
                } else {
                    throw new Exception('Attribute `name` required!');
                }
            }
            $item_name = trim($node->getAttribute('name'));
            $this->checkName($item_name);
            $item = $this->makeItemObject(FFanStr::camelName($item_name), $node);
            if (isset($item_arr[$item_name])) {
                throw new Exception('Item name:' . $item_name . ' 已经存在');
            }
            $item_arr[$item_name] = $item;
        }
        $extend_struct = null;
        $build_opt = $this->manager->getCurrentBuildOpt();
        //继承关系
        if ($struct->hasAttribute('extend')) {
            if (!$allow_extend) {
                throw new Exception('Extend只允许在<action>标签内使用');
            }
            $struct_name = trim($struct->getAttribute('extend'));
            $struct_name = $this->getFullName($struct_name);
            $conf_suffix = $build_opt->getConfig('struct_class_suffix');
            if (!empty($conf_suffix)) {
                $struct_name .= FFanStr::camelName($conf_suffix);
            }
            $extend_struct = $this->manager->loadRequireStruct($struct_name, $this->xml_file_name);
            if (null === $extend_struct) {
                throw new Exception('无法找到Struct ' . $struct_name);
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
        $class_name_suffix = $build_opt->getConfig(Struct::getTypeName($type) .'_class_suffix');
        $struct_class_name = $this->joinName(FFanStr::camelName($class_name_suffix), $class_name);
        $struct_obj = new Struct($this->namespace, $struct_class_name, $this->xml_file_name, $type, $is_public);
        //如果有注释
        if ($struct->hasAttribute('note')) {
            $struct_obj->setNote($struct->getAttribute('note'));
        }
        foreach ($item_arr as $name => $item) {
            $struct_obj->addItem($name, $item);
        }
        if ($extend_struct) {
            $struct_obj->extend($extend_struct);
        }
        $this->manager->addStruct($struct_obj);
        return $struct_obj;
    }

    /**
     * 生成item对象
     * @param string $name
     * @param \DOMNode $dom_node 节点
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
                $struct_obj->addReferType($this->current_struct_type);
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
            $plugin = $this->manager->getPlugin($plugin_name);
            if (!$plugin) {
                continue;
            }
            $plugin->init($tmp_node, $item);
        }
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
    private function getFullName($struct_name)
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
     * @param \DOMNode $item 节点
     * @return Struct
     */
    private function parsePrivateStruct($name, \DOMNode $item)
    {
        //如果是引用其它Struct，加载其它Struct
        /** @var \DOMElement $item */
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
