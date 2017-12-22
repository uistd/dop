<?php

namespace UiStd\Dop\Schema;

use UiStd\Dop\Build\BuildOption;
use UiStd\Dop\Exception;
use UiStd\Dop\Manager;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Common\Utils as UisUtils;
use UiStd\Common\Str as UisStr;

/**
 * Class File
 * @package UiStd\Dop\Scheme
 */
class File
{
    /**
     * @var Manager;
     */
    private $manager;

    /**
     * @var \DOMDocument xml_handle
     */
    private $xml_handle;

    /**
     * @var \DOMXpath
     */
    private $path_handle;

    /**
     * @var string
     */
    private $file_name;

    /**
     * @var BuildOption 生成参数配置
     */
    private $build_opt;

    /**
     * @var string 命名空间
     */
    private $namespace;

    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * @var array 依赖
     */
    private static $require_ns = array();

    /**
     * @var array 已经解析的namespace列表
     */
    private static $namespace_list = array();

    /**
     * Scheme constructor.
     * @param Manager $manager
     * @param $file_name
     * @throws Exception
     */
    public function __construct(Manager $manager, $file_name)
    {
        $this->protocol = Protocol::getInstance($manager);
        $this->manager = $manager;
        $base_path = $manager->getBasePath();
        $this->namespace = self::fileToNamespace($file_name);
        static::$namespace_list[$this->namespace] = true;
        $full_name = UisUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new Exception('找不到协议文件:' . $full_name);
        }
        $this->xml_handle = new \DOMDocument();
        $this->xml_handle->load($full_name);
        $this->file_name = $full_name;
        $this->build_opt = $manager->getCurrentBuildOpt();
        $this->parse();
    }

    /**
     * 解析
     */
    public function parse()
    {
        Exception::pushStack('Parse file:'. $this->file_name);
        $this->queryModel();
        $this->queryAction();
        $this->queryData();
        $this->queryShader();
        Exception::popStack();
    }

    /**
     * 解析model
     * @throws Exception
     */
    private function queryModel()
    {
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/model');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            Exception::pushStack($this->traceInfo($struct));
            $this->parseModel($struct);
            Exception::popStack();
        }
    }

    /**
     * 解析Action协议
     */
    private function queryAction()
    {
        $path_handle = $this->getPathHandle();
        $action_list = $path_handle->query('/protocol/action');
        if (null === $action_list) {
            return;
        }
        for ($i = 0; $i < $action_list->length; ++$i) {
            /** @var \DOMElement $action */
            $action = $action_list->item($i);
            Exception::pushStack($this->traceInfo($action));
            if (!$action->hasAttribute('name')) {
                throw new Exception('Action must have name attribute');
            }
            $name = $action->getAttribute('name');
            if (false !== strpos($name, '/')) {
                $name = str_replace('/', '_', $name);
                $action->setAttribute('name', $name);
            }
            $this->parseAction($name, $action);
            Exception::popStack();
        }
    }

    /**
     * 解析request
     * @param string $name
     * @param \DOMElement $action
     * @throws Exception
     */
    private function parseAction($name, \DOMElement $action)
    {
        $node_list = $action->childNodes;
        $request_count = 0;
        $response_count = 0;
        $action_method = $action->getAttribute('method');
        if (empty($action_method)) {
            $action_method = 'get';
        }
        $action_object = new Action($action);
        $extra_packer = $this->parseExtraPacer($action);
        $note = $action->getAttribute('note');
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $node_name = strtolower($node->nodeName);
            if ('request' === $node_name) {
                if (++$request_count > 1) {
                    throw new Exception('Only one request node allowed');
                }
                $method = $node->getAttribute('method');
                //如果在 request 上没有指定method, 尝试使用 action 上的method
                if (empty($method)) {
                    $method = $action_method;
                    $node->setAttribute('method', $method);
                }
                $node->setAttribute('class_name', $name . '_request');
                $type = Model::TYPE_REQUEST;
            } elseif ('response' === $node_name) {
                if (++$response_count > 1) {
                    throw new Exception('Only one response node allowed');
                }
                $node->setAttribute('class_name', $name . '_response');
                $this->responseModelClassName($node, $name);
                $type = Model::TYPE_RESPONSE;
            } else {
                throw new Exception('Unknown node:' . $node_name);
            }
            if (!empty($note)) {
                $node->setAttribute('note', $note);
            }
            /** @var \DOMElement $node */
            if (!empty($extra_packer)) {
                $node->setAttribute('packer-extra', $extra_packer);
            }
            $model_class_name = $this->parseModel($node, $type);
            if (Model::TYPE_REQUEST === $type) {
                $action_object->setRequestModel($model_class_name);
            } else {
                $action_object->setResponseMode($model_class_name);
            }
            $model = $this->protocol->getModel($model_class_name);
            if (null === $model) {
                throw new Exception('Unknown error');
            }
            $model->setAction($action_object);
        }
    }

    /**
     * 自动给response下面的model生成class_name
     * @param \DOMElement $struct_node
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
            $node->setAttribute('class_name', $action_name . '_' . $name);
        }
    }


    /**
     * 解析extra-packer设置
     * @param \DOMElement $node
     * @return string|null
     */
    private function parseExtraPacer($node)
    {
        return $node->getAttribute('packer-extra');
    }

    /**
     * 解析Data协议
     */
    private function queryData()
    {
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/data');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $data_node */
            $data_node = $node_list->item($i);
            Exception::pushStack($this->traceInfo($data_node));
            $this->parseModel($data_node, Model::TYPE_DATA);
            Exception::popStack();
        }
    }

    /**
     * 解析render
     */
    private function queryShader()
    {
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/shader');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $shader_node */
            $shader_node = $node_list->item($i);
            Exception::pushStack($this->traceInfo($shader_node));
            if (empty($shader_node->getAttribute('name'))) {
                throw new Exception('Shader name missing'. PHP_EOL. $shader_node->C14N());
            }
            $shader = new Shader($shader_node);
            $this->protocol->addShader($shader);
            Exception::popStack();
        }
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
     * 解析一组协议
     * @param \DomElement $model_node
     * @param int $type
     * @return string
     * @throws Exception
     */
    private function parseModel(\DomElement $model_node, $type = Model::TYPE_STRUCT)
    {
        //model 取名 的时候，优先使用 class_name
        $name_attr = $model_node->hasAttribute('class_name') ? 'class_name' : 'name';
        $class_name = $model_node->getAttribute($name_attr);
        if (is_string($class_name)) {
            $class_name = trim($class_name);
        }
        if (empty($class_name)) {
            throw new Exception('缺少 name 属性');
        }
        $node_list = $model_node->childNodes;
        $item_arr = array();
        $name_conflict = array();
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            //插件
            if ($this->isPluginNode($node)) {
                continue;
            }
            Exception::pushStack($this->traceInfo($node));
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
            $item_name = trim($node->getAttribute('name'));
            $this->checkName($item_name);
            if (isset($item_arr[$item_name])) {
                throw new Exception('Item name:' . $item_name . ' 已经存在');
            }
            $camel_name = UisStr::camelName($item_name);
            if (isset($name_conflict[$camel_name])) {
                throw new Exception('Item name 驼峰命名:' . $camel_name . ' 冲突');
            }
            $name_conflict[$camel_name] = true;
            $item = $this->makeItem($item_name, $node);
            $item_arr[$item_name] = $item;
            Exception::popStack();
        }
        $extend_name = '';
        //继承关系
        if ($model_node->hasAttribute('extend')) {
            Exception::pushStack('Parse extend');
            $extend_name = trim($model_node->getAttribute('extend'));
            if (empty($extend_name)) {
                throw new Exception('extend 不能为空');
            }
            //补全
            if (false === strpos($extend_name, '/')) {
                $extend_name = $this->namespace . '/' . $extend_name;
            } else {
                $this->protocol->setFileRequire($this->namespace, dirname($extend_name));
            }
            Exception::popStack();
        }
        //如果item为空
        if (empty($item_arr)) {
            //完全继承
            if (!empty($extend_name)) {
                self::addExtend($extend_name, $model_node->C14N());
                return $extend_name;
            } //data 和 struct不允许空item
            elseif (Model::TYPE_STRUCT === $type || Model::TYPE_DATA === $type) {
                throw new Exception('Empty struct');
            }
        }
        $class_name = trim($class_name);
        $model = new Model($this->namespace, $class_name, $type, $model_node);
        foreach ($item_arr as $name => $item) {
            $model->addItem($name, $item);
        }
        if (!empty($extend_name)) {
            self::addExtend($extend_name, $model_node->C14N());
            $model->setExtend($extend_name);
        }
        $this->protocol->addModel($this->namespace, $model);
        return $this->namespace . '/' . $class_name;
    }

    /**
     * 设置extend
     * @param string $extend_name
     * @param string $doc
     */
    private static function addExtend($extend_name, $doc)
    {
        $ns = dirname($extend_name);
        if (isset(self::$require_ns[$ns]) || '.' === $ns) {
            return;
        }
        self::$require_ns[$ns] = $doc;
    }

    /**
     * 获取需要加载的命名空间
     * @return array|null
     */
    public static function getRequireNameSpace()
    {
        if (empty(self::$require_ns)) {
            return null;
        }
        $require_ns = self::$require_ns;
        self::$require_ns = array();
        foreach ($require_ns as $ns => $doc) {
            if (isset(self::$namespace_list[$ns])) {
                unset($require_ns[$ns]);
            }
        }
        return $require_ns;
    }

    /**
     * 生成item对象
     * @param string $name
     * @param \DOMElement $item_node 节点
     * @return Item
     * @throws Exception
     */
    private function makeItem($name, $item_node)
    {
        $type = ItemType::getType($item_node->nodeName);
        if (null === $type) {
            throw new Exception('Unknown type `' . $item_node->nodeName . '`');
        }
        $item = new Item($type, $item_node, $this->namespace);
        switch ($type) {
            case ItemType::ARR:
                $list_item = $this->parseList($name, $item_node);
                $item->addSubItem($list_item);
                break;
            case ItemType::MAP:
                $this->parseMap($name, $item_node, $item);
                break;
            case ItemType::STRUCT:
                $sub_model_name = $this->parsePrivateStruct($name, $item_node);
                if (false === strpos($sub_model_name, '/')) {
                    $sub_model_name = $this->namespace . '/' . $sub_model_name;
                }
                $item->setSubModel($sub_model_name);
                break;
        }
        $this->parsePlugin($item_node, $item);
        return $item;
    }

    /**
     * 解析list
     * @param string $name
     * @param \DomElement $item 节点
     * @return Item
     * @throws Exception
     */
    private function parseList($name, $item)
    {
        $item_list = $item->childNodes;
        $type_node = null;
        Exception::pushStack($this->traceInfo($item));
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
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
            $short_value = $item->getAttribute('item');
            if (!empty($short_value)) {
                $type_node = $this->parseListItem($item, $short_value);
            }
        }
        if (null === $type_node) {
            throw new Exception('List下必须包括一个指定list类型的节点');
        }
        if ($type_node->hasAttribute('name')) {
            $name = trim($type_node->getAttribute('name'));
        }
        $re = $this->makeItem($name, $type_node);
        Exception::popStack();
        return $re;
    }

    /**
     * 解析list简要书写方式
     * @param \DomElement $list_item
     * @param string $short_value
     * @return \DomElement|null
     */
    private function parseListItem($list_item, $short_value)
    {
        $node = null;
        $item_type = ItemType::getType($short_value);
        if ($item_type && ItemType::ARR !== $item_type && ItemType::STRUCT !== $item_type) {
            $node = new \DomElement($short_value, '');
            $list_item->appendChild($node);
        }
        //如果是指 model
        elseif (0 === strpos($short_value, 'model.')) {
            $model_name = str_replace('model.', '', $short_value);
            $node = new \DomElement('model');
            $list_item->appendChild($node);
            $node->setAttribute('extend', $model_name);
        }
        return $node;
    }

    /**
     * 解析Map
     * @param string $name
     * @param \DOMNode $item 节点
     * @param Item $item_obj
     * @throws Exception
     */
    private function parseMap($name, \DOMNode $item, Item $item_obj)
    {
        $item_list = $item->childNodes;
        $key_node = null;
        $value_node = null;
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
            if (XML_ELEMENT_NODE !== $tmp_node->nodeType) {
                continue;
            }
            if ($this->isPluginNode($tmp_node)) {
                continue;
            }
            if (null === $key_node) {
                $key_node = $tmp_node;
                $key_item = $this->makeItem($name, $key_node);
                $item_obj->addSubItem($key_item);
            } elseif (null === $value_node) {
                $value_node = $tmp_node;
                if ($tmp_node->hasAttribute('name')) {
                    $tmp_name = trim($tmp_node->getAttribute('name'));
                    if (!empty($tmp_name)) {
                        $this->checkName($tmp_name);
                        $name = UisStr::camelName($tmp_name);
                    }
                }
                $value_item = $this->makeItem($name, $value_node);
                $item_obj->addSubItem($value_item);
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
     * 解析私有的struct
     * @param string $name
     * @param \DOMElement $item_node 节点
     * @return string
     */
    private function parsePrivateStruct($name, $item_node)
    {
        Exception::pushStack($this->traceInfo($item_node));
        if ($item_node->hasAttribute('class_name')) {
            $class_name = trim($item_node->getAttribute('class_name'));
            if (!empty($class_name)) {
                $name = $class_name;
            }
        }
        $item_node->setAttribute('name', $name);
        $re = $this->parseModel($item_node, Model::TYPE_STRUCT);
        Exception::popStack();
        return $re;
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
            Exception::pushStack($this->traceInfo($tmp_node));
            //如果 节点中包含 extend, 要检测文件是否被加载
            if ($tmp_node->hasAttribute('extend')) {
                $extend = $tmp_node->getAttribute('extend');
                //去掉一层 item 如:/common/user/puid puid是字段, 去掉
                self::addExtend(dirname($extend), '');
            }
            $plugin_name = str_replace('plugin_', '', $tmp_node->nodeName);
            $plugin = new Plugin($plugin_name, $tmp_node);
            $item->addPlugin($plugin_name, $plugin);
            Exception::popStack();
        }
    }

    /**
     * 是否是插件节点
     * @param \DOMNode $node
     * @return bool
     */
    private function isPluginNode($node)
    {
        if (null !== ItemType::getType($node->nodeName)) {
            return false;
        }
        //如果以plugin_开始的字符串, 肯定是插件
        if (0 === strpos($node->nodeName, 'plugin_')) {
            return true;
        }
        return $this->manager->hasPlugin($node->nodeName);
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
     * @param string $file_name
     * @return string
     */
    public static function fileToNamespace($file_name)
    {
        $path = dirname($file_name);
        if ('.' === $path{0}) {
            $path = '/';
        } else {
            $path = '/' . $path . '/';
        }
        return $path . basename($file_name, '.xml');
    }

    /**
     * 生成追踪数据
     * @param \DOMElement $node
     * @return string
     */
    private function traceInfo($node)
    {
        return 'File:'. $this->file_name . ' Line '. $node->getLineNo(). PHP_EOL . $node->C14N() . PHP_EOL;
    }
}
