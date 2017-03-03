<?php
namespace ffan\dop;

use ffan\php\utils\Str as FFanStr;
use ffan\php\utils\Utils as FFanUtils;

/**
 * Class XmlProtocol
 * @package ffan
 */
class XmlProtocol
{
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
     * @var int 当前行号
     */
    private $current_line_no = 0;

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
    private static $http_method_list = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

    /**
     * ProtocolXml constructor.
     * @param string $base_path 基础目录
     * @param string $file_name 协议文件
     */
    public function __construct($base_path, $file_name)
    {
        $full_name = FFanUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new \InvalidArgumentException('Invalid file:' . $full_name);
        }
        $this->file_name = $full_name;
        $this->xml_handle = new \DOMDocument();
        $this->xml_handle->load($full_name);
        $dir_name = dirname($file_name);
        if ('.' === $dir_name) {
            $dir_name = DIRECTORY_SEPARATOR;
        }
        if (DIRECTORY_SEPARATOR !== $dir_name[0]) {
            $dir_name = DIRECTORY_SEPARATOR . $dir_name;
        }
        $this->namespace = $dir_name . '/' . basename($file_name, '.xml');
        $this->parse();
    }

    /**
     * 解析该xml文件
     */
    private function parse()
    {
        $this->path_handle = new \DOMXpath($this->xml_handle);
        $this->queryStruct();
        $this->parseAction();
    }

    /**
     * 解析公用struct
     */
    private function queryStruct()
    {
        $node_list = $this->path_handle->query('/protocol/struct');
        if (null === $node_list) {
            return;
        }
        for ($i = 0; $i < $node_list->length; ++$i) {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->current_line_no = $struct->getLineNo();
            if (!$struct->hasAttribute('name')) {
                $msg = $this->errMsg('Struct must have name attribute');
                throw new DOPException($msg);
            }
            $name = trim($struct->getAttribute('name'));
            $name = FFanStr::camelName($name, true);
            $is_public = 1 === (int)$struct->getAttribute('public');
            $struct_obj = $this->parseStruct($name, $is_public, $struct);
            ProtocolManager::addStruct($struct_obj);
        }
    }

    /**
     * 解析Action
     */
    private function parseAction()
    {
        $action_list = $this->path_handle->query('/protocol/action');
        if (null === $action_list) {
            return;
        }
        for ($i = 0; $i < $action_list->length; ++$i) {
            /** @var \DOMElement $action */
            $action = $action_list->item($i);
            $this->current_line_no = $action->getLineNo();
            if (!$action->hasAttribute('name')) {
                $msg = $this->errMsg('Action must have name attribute');
                throw new DOPException($msg);
            }
            $name = trim($action->getAttribute('name'));
            if ($action->hasAttribute('method')) {
                $method = trim($action->getAttribute('method'));
                if (!isset(self::$http_method_list[strtoupper($method)])) {
                    throw new DOPException($this->errMsg($method . ' is not support http method type'));
                }
                $name = $method . $name;
            }
            $name = FFanStr::camelName($name, true);
            $struct_obj = $this->parseStruct($name, false, $action);
            ProtocolManager::addStruct($struct_obj);
        }
    }

    /**
     * 解析struct
     * @param string $class_name 上级类名
     * @param bool $is_public
     * @param \DomElement $struct
     * @return Struct
     * @throws DOPException
     */
    private function parseStruct($class_name, $is_public, \DomElement $struct)
    {
        $node_list = $struct->childNodes;
        $struct_obj = new Struct($this->namespace, $class_name, $is_public);
        for ($i = 0; $i < $node_list->length; ++$i) {
            $node = $node_list->item($i);
            if (XML_ELEMENT_NODE !== $node->nodeType) {
                continue;
            }
            $this->current_line_no = $node->getLineNo();
            /** @var \DOMElement $node */
            if (!$node->hasAttribute('name')) {
                throw new DOPException($this->errMsg('Attribute `name` required!'));
            }
            $item_name = trim($node->getAttribute('name'));
            $this->checkName($item_name);
            $item = $this->makeItemObject($class_name . ucfirst($item_name), $node);
            if ($struct_obj->hasItem($item_name)) {
                throw new DOPException($this->errMsg('Item name:' . $item_name . ' 已经存在'));
            }
            $struct_obj->addItem($item_name, $item);
        }
        return $struct_obj;
    }

    /**
     * 生成item对象
     * @param string $name
     * @param \DOMNode $item 节点
     * @return Item
     * @throws DOPException
     */
    private function makeItemObject($name, \DOMNode $item)
    {
        $type = ItemType::getType($item->nodeName);
        if (null === $type) {
            throw new DOPException($this->errMsg('Unknown type `' . $item->nodeName . '`'));
        }
        switch ($type) {
            case ItemType::STRING:
                $item_obj = new StringItem($name);
                break;
            case ItemType::FLOAT:
                $item_obj = new FloatItem($name);
                break;
            case ItemType::BINARY:
                $item_obj = new BinaryItem($name);
                break;
            case ItemType::ARR:
                $item_obj = new ListItem($name);
                $list_item = $this->parseList($name, $item);
                $item_obj->setItem($list_item);
                break;
            case ItemType::STRUCT:
                $item_obj = new StructItem($name);
                $struct_name = $this->parsePrivateStruct($name, $item);
                $item_obj->setStructName($struct_name);
                break;
            case ItemType::MAP:
                $item_obj = new MapItem($name);
                break;
            case ItemType::INT:
            default:
                $item_obj = new IntItem($name);
                $item_obj->setByte(ItemType::getIntByte($type));
                break;
        }
        return $item_obj;
    }

    /**
     * 解析list
     * @param string $name
     * @param \DOMNode $item 节点
     * @return Item
     * @throws DOPException
     */
    private function parseList($name, \DOMNode $item)
    {
        $item_list = $item->childNodes;
        $type_node = null;
        for ($i = 0; $i < $item_list->length; ++$i) {
            $tmp_node = $item_list->item($i);
            $this->current_line_no = $tmp_node->getLineNo();
            if (XML_ELEMENT_NODE !== $tmp_node->nodeType) {
                continue;
            }
            if (null !== $type_node) {
                throw new DOPException($this->errMsg('List只能有一个节点'));
            }
            $type_node = $tmp_node;
        }
        if (null === $type_node) {
            throw new DOPException($this->errMsg('List下必须包括一个指定list类型的节点'));
        }
        $name .= 'List';
        return $this->makeItemObject($name, $type_node);
    }

    /**
     * 把名字压入stack，避免冲突
     * @param string $name
     * @throws DOPException
     */
    private function checkName($name)
    {
        if (!isset($this->name_stack[$name])) {
            throw new DOPException($this->errMsg('Name:' . $name . ' 已经存在'));
        }
        //是否可以做类名
        if (empty($name) || 0 === preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) > 0) {
            throw new DOPException('Name:' . $name . ' is invalid');
        }
        $this->name_stack[$name] = true;
    }

    /**
     * 获取类名全路径
     * @param string $struct_name
     * @return string
     * @throws DOPException
     */
    private function getFullName($struct_name)
    {
        if (empty($struct_name)) {
            throw new DOPException($this->errMsg('struct name error'));
        }
        //名称不合法
        if (!preg_match('/^\/?[a-zA-Z_][a-zA-Z_a\d]*(\/[a-zA-Z_][a-zA-Z_\d]*)*$/', $struct_name)) {
            throw new DOPException($this->errMsg('Invalid struct name:' . $struct_name));
        }
        //补全
        if ('/' !== $struct_name[0]) {
            //如果完全没有 / 表示当前namespace 
            if (false === strpos($struct_name, '/')) {
                $struct_name = $this->namespace . $struct_name;
            } //同级目录下
            else {
                $struct_name = basename($this->namespace) . '/' . $struct_name;
            }
        }
        return $struct_name;
    }

    /**
     * 解析私有的struct
     * @param string $name
     * @param \DOMNode $item 节点
     * @return String
     * @throws DOPException
     */
    private function parsePrivateStruct($name, \DOMNode $item)
    {
        //如果是引用其它Struct，加载其它Struct
        /** @var \DOMElement $item */
        if ($item->hasAttribute('class')) {
            $name = $this->getFullName(trim($item->getAttribute('class')));
            $refer_struct = ProtocolManager::loadStruct($name);
            if (null === $refer_struct) {
                throw new DOPException($this->errMsg('无法找到Struct ' . $name));
            } elseif (!$refer_struct->isPublic() && $this->namespace !== $refer_struct->getNamespace()) {
                throw new DOPException($this->errMsg('struct:' . $name . ' is not public!'));
            }
            return $name;
        } //私有的struct
        else {
            $struct_obj = $this->parseStruct($name, false, $item);
            ProtocolManager::addStruct($struct_obj);
        }
        return $name;
    }

    /**
     * 错误消息
     * @param string $msg 消息
     * @param int $line_no 行号
     * @return string
     */
    private function errMsg($msg, $line_no = 0)
    {
        return $msg . $this->getDocInfo($line_no) . PHP_EOL;
    }

    /**
     * 获取文档信息
     * @param int $line_no
     * @return string
     */
    private function getDocInfo($line_no = 0)
    {
        if (0 === $line_no) {
            $line_no = $this->current_line_no;
        }
        return 'File:' . $this->file_name . ' Line:' . $line_no;
    }
}
