<?php

namespace FFan\Dop\Scheme;

use FFan\Dop\Build\BuildOption;
use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Dop\Protocol\ItemType;
use FFan\Std\Common\Utils as FFanUtils;
use FFan\Std\Common\Str as FFanStr;

/**
 * Class File
 * @package FFan\Dop\Scheme
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
     * @var array model列表
     */
    private $model_list;

    /**
     * @var Shader[] 着色器
     */
    private $shader;

    /**
     * @var string
     */
    private $file_name;

    /**
     * @var BuildOption 生成参数配置
     */
    private $build_opt;

    /**
     * Scheme constructor.
     * @param Manager $manager
     * @param $file_name
     * @throws Exception
     */
    public function __construct(Manager $manager, $file_name)
    {
        $this->manager = $manager;
        $base_path = $manager->getBasePath();
        $this->xml_file_name = $file_name;
        $full_name = FFanUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new Exception('找不到协议文件:' . $full_name);
        }
        $this->xml_handle = new \DOMDocument();
        $this->xml_handle->load($full_name);
        $this->xml_file_name = $file_name;
        $this->file_name = $full_name;
        $this->build_opt = $manager->getCurrentBuildOpt();
        $this->parse();
    }

    /**
     * 解析
     */
    public function parse()
    {
        $this->queryModel('struct');
        $this->queryModel('model');
        $this->queryAction();
        $this->queryData();
        $this->queryShader();
    }

    /**
     * 解析model
     * @param string $tag_name
     * @throws Exception
     */
    private function queryModel($tag_name)
    {
        $path_handle = $this->getPathHandle();
        $node_list = $path_handle->query('/protocol/'. $tag_name);
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->setLineNumber($struct->getLineNo());
            $this->parseModel($struct);
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
            Manager::setCurrentNode($action);
            $this->setLineNumber($action->getLineNo());
            if (!$action->hasAttribute('name')) {
                throw new Exception('Action must have name attribute');
            }
            //action 的name支持 /aa/bb 的格式
            $name = $action->getAttribute('name');
            $this->parseAction($name, $action);
            Manager::setCurrentNode(null);
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
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            $this->setLineNumber($node->getLineNo());
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $node->setAttribute('name',$name);
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
                $type = Model::TYPE_REQUEST;
            } elseif ('response' === $node_name) {
                if (++$response_count > 1) {
                    throw new Exception('Only one response node allowed');
                }
                $type = Model::TYPE_RESPONSE;
            } else {
                throw new Exception('Unknown node:' . $node_name);
            }
            /** @var \DOMElement $node */
            $this->parseModel($node, $type);
        }
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
            $this->setLineNumber($data_node->getLineNo());
            $this->parseModel($data_node, Model::TYPE_DATA);
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
            Manager::setCurrentNode($shader_node);
            $this->setLineNumber($shader_node->getLineNo());
            $shader = new Shader($shader_node);
            $this->shader[] = $shader;
            Manager::setCurrentNode(null);
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
     * 设置行号
     * @param string $line_number
     */
    private function setLineNumber($line_number)
    {
        $position_info = 'File:' . $this->file_name . ' Line:' . $line_number;
        Exception::setAppendMsg($position_info);
    }

    /**
     * 解析一组协议
     * @param \DomElement $model_node
     * @param int $type
     * @throws Exception
     */
    private function parseModel(\DomElement $model_node, $type = Model::TYPE_STRUCT)
    {
        Manager::setCurrentNode($model_node);
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
            if (isset($item_arr[$item_name])) {
                throw new Exception('Item name:' . $item_name . ' 已经存在');
            }
            $item = $this->makeItem($item_name, $node);
            $item_arr[$item_name] = $item;
        }
        $extend_name = '';
        //继承关系
        if ($model_node->hasAttribute('extend')) {
            $extend_name = trim($model_node->getAttribute('extend'));
            if (empty($extend_name)) {
                throw new Exception('extend 不能为空');
            }
        }
        //如果item为空
        if (empty($item_arr)) {
            //完全继承
            if (!empty($extend_name)) {
                return;
            } //data 和 struct不允许空item
            elseif (Model::TYPE_STRUCT === $type || Model::TYPE_DATA === $type) {
                throw new Exception('Empty struct');
            }
        }
        $model = new Model($class_name, $model_node);
        foreach ($item_arr as $name => $item) {
            $model->addItem($name, $item);
        }
        if (!empty($extend_name)) {
            $model->setExtend($extend_name);
        }
        Manager::setCurrentNode(null);
        if (isset($this->model_list[$type][$class_name])) {
            throw new Exception('class_name 类名冲突');
        }
        $this->model_list[$type][$class_name] = $model;
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
        $item = new Item($type, $item_node);
        switch ($type) {
            case ItemType::ARR:
                $list_item = $this->parseList($name, $item_node);
                $item->addSubItem($list_item);
                break;
            case ItemType::MAP:
                $this->parseMap($name, $item_node, $item);
                break;
            case ItemType::STRUCT:
                $this->parsePrivateStruct($name, $item_node, $item);
                break;
        }
        $this->parsePlugin($item_node, $item);
        return $item;
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
            $name = trim($type_node->getAttribute('name'));
        }
        return $this->makeItem($name, $type_node);
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
            $this->setLineNumber($tmp_node->getLineNo());
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
                        $name = FFanStr::camelName($tmp_name);
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
     * @param \DOMElement $item 节点
     * @param Item $item_object
     */
    private function parsePrivateStruct($name, \DOMElement $item, Item $item_object)
    {
        $item_object->setRequireModel($name);
        if (!$item->hasAttribute('name')) {
            $item->setAttribute('name', $name);
        }
        $this->parseModel($item);
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
            Manager::setCurrentNode($tmp_node);
            $this->setLineNumber($tmp_node->getLineNo());
            $plugin_name = substr($tmp_node->nodeName, strlen('plugin_'));
            $plugin = new Plugin($plugin_name, $tmp_node);
            $item->addPlugin($plugin_name, $plugin);
            Manager::setCurrentNode(null);
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
     * 修正字段名
     * @param string $item_name
     * @return string
     */
    public function fixItemName($item_name)
    {
        return FFanStr::camelName($item_name, false);
    }
}