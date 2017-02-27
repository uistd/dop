<?php
namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;

/**
 * Class ProtocolXml
 * @package ffan
 */
class ProtocolXml
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
     * @var string 命名空间
     */
    private $namespace;

    /**
     * ProtocolXml constructor.
     * @param string $base_path 基础目录
     * @param string $file_name 协议文件
     */
    public function __construct($base_path, $file_name)
    {
        $full_name = FFanUtils::joinFilePath($base_path, $file_name);
        if (!is_file($full_name)) {
            throw new \InvalidArgumentException('Invalid file');
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
        $this->namespace = $dir_name;
        $this->parse();
    }

    /**
     * 解析该xml文件
     */
    private function parse()
    {
        $this->path_handle = new \DOMXpath($this->xml_handle);
        $this->queryStruct();
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
        for ( $i = 0; $i < $node_list->length; ++$i )
        {
            /** @var \DOMElement $struct */
            $struct = $node_list->item($i);
            $this->parseStruct($struct);
        }
    }

    /**
     * 解析struct
     * @param \DomElement $struct
     * @throws DOPException
     */
    private function parseStruct(\DomElement $struct)
    {
        if (!$struct->hasAttribute('name')) {
            $msg = $this->errMsg('Struct must have name attribute', $struct->getLineNo());
            throw new DOPException($msg);
        }
        $node_list = $struct->childNodes;
        $name = $struct->getAttribute('name');
        $item_struct = new StructItem($name, $this->getDocInfo());
        for ( $i = 0; $i < $node_list->length; ++$i )
        {
            $node = $node_list->item($i);
            $item = $this->parseItem($node);
        }
    }

    /**
     * 解析字段
     * @param \DOMNode $item
     * @return Item;
     * @throws DOPException
     */
    private function parseItem(\DOMNode $item)
    {
        /** @var \DOMElement $item */
        $this->current_line_no = $item->getLineNo();
        if (!$item->hasAttribute('type')) {
            $msg = $this->errMsg('Attribute `type` is required');
            throw new DOPException($msg);
        }
        $type_str = trim($item->getAttribute('type'));
        if (empty($type_str)) {
            $msg = $this->errMsg('Attribute `type` is required');
            throw new DOPException($msg);
        }
        $item_type = ItemType::getType($type_str);
        if (null !== $item_type) {
            $class_name = ItemType::getClassName($item_type);
            /** @var Item $item_obj */
            $item_obj = new $class_name($item->nodeName, $this->getDocInfo());
        }
    }

    /**
     * 生成item对象
     * @param string $name
     * @param string $type_str 类型字符
     * @param \DOMNode $node 节点
     * @param null $parent_type 上一级类型
     * @return Item
     * @throws DOPException
     */
    private function makeItemObject($name, $type_str, \DOMNode $node, $parent_type = null)
    {
        $type_str = trim($type_str);
        $type = ItemType::getType($type_str);
        if (null === $type) {
            throw new DOPException($this->errMsg('Unknown type_str'));
        }
        switch ($type) {
            case ItemType::INT:
                $item_obj = new IntItem($name, $this->getDocInfo());
                $item_obj->setByte(ItemType::getIntByte($type));
                break;
            case ItemType::STRING:
                $item_obj = new StringItem($name, $this->getDocInfo());
                break;
            case ItemType::FLOAT:
                $item_obj = new FloatItem($name, $this->getDocInfo());
                break;
            case ItemType::BINARY:
                $item_obj = new BinaryItem($name, $this->getDocInfo());
                break;
            case ItemType::ARR:
                $item_obj = new ListItem($name, $this->getDocInfo());
                $sub_type = substr($type_str, 1, -1);
                $name .= 'Arr';
                $item_obj->setItem($this->makeItemObject($name, $sub_type, $node));
                break;
            case ItemType::STRUCT:
                $item_obj = new StructItem($name, $this->getDocInfo());
                $struct_name = trim(substr($type_str, 1, -1));
                if (!empty($struct_name)) {
                    $item_obj->setStructName($struct_name);
                }
                break;
            case ItemType::MAP:
                break;
        }
    }

    /**
     * 解析私有的struct
     */
    private function parsePrivateStruct()
    {
        
    }

    /**
     * 解析结构体
     * @param $type_str
     * @param \DOMNode $item
     * @return StructItem
     * @throws DOPException
     */
    private function typeStruct($type_str, \DOMNode $item)
    {
        //最后一个字符必须是}
        if ('}' !== $type_str[strlen($type_str) - 1]) {
            throw new DOPException($this->errMsg('type error'));
        }
        $struct_name = trim(substr($type_str, 1, -1));
        if (empty($struct_name)) {
            throw new DOPException($this->errMsg('struct error'));
        }
        $item_obj = new StructItem($item->nodeName, $this->getDocInfo());
        $item_obj->setStructName($struct_name);
        return $item_obj;
    }

    /**
     * 数组类型
     * @param string $type
     * @throws DOPException
     */
    private function typeList($type)
    {
        //最后一个字符必须是}
        if (']' !== $type[strlen($type) - 1]) {
            throw new DOPException($this->errMsg('type error'));
        }
        $list_type = trim(substr($type, 1, -1));
        //如果中间包含 => 表示map
        if (false !== strpos($list_type, '=>')) {
            
        }
        if (empty($list_type)) {
            throw new DOPException($this->errMsg('list error'));
        }
        $type = ItemType::getType($list_type);
        if (null === $type) {
            $type = ItemType::STRUCT;
        }
    }

    /**
     * 错误消息
     * @param string $msg 消息
     * @param int $line_no 行号
     * @return string
     */
    private function errMsg($msg, $line_no = 0)
    {
        return $msg .$this->getDocInfo($line_no). PHP_EOL;
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
        return 'File:'.$this->file_name .' Line:'. $line_no;
    }
}
