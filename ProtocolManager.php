<?php
namespace ffan\dop;

use ffan\php\utils\Str as FFanStr;
use ffan\php\utils\Utils as FFanUtils;

/**
 * Class ProtocolManager
 * @package ffan\dop
 */
class ProtocolManager
{
    /**
     * @var array 所有的struct列表
     */
    private $struct_list = [];

    /**
     * @var array 解析过的xml列表
     */
    private $xml_list = [];

    /**
     * @var string 当前正在使用的文件和行号
     */
    private $protocol_doc_info = '';

    /**
     * @var string 协议基础路径
     */
    private $base_path;

    /**
     * @var string 协议生成文件的路径
     */
    private $build_path;
    
    /**
     * @var string 命名空间
     */
    private $main_namespace = 'ffan\dop';

    /**
     * @var array 配置
     */
    private $config;

    /**
     * 初始化
     * ProtocolManager constructor.
     * @param string $base_path 协议文件所在的目录
     * @param string $build_path 生成文件的目录
     * @param array $config 其它配置项
     * @throws DOPException
     */
    public function __construct($base_path, $build_path = 'dop', array $config = array())
    {
        if (!is_dir($base_path)) {
            throw new DOPException('Protocol path:'. $base_path .' not exist!');
        }
        if (!is_readable($base_path)) {
            throw new DOPException('Protocol path:'. $base_path .' is not readable');
        }
        //如果build_path参数不正确，修正为dop
        if (!is_string($build_path) || !FFanstr::isValidVarName($build_path)) {
            $build_path = 'dop';
        }
        $this->build_path = $build_path;
        $this->base_path = $base_path;
        //如果配置了main_namespace(命名空间前缀)
        if (isset($config['main_namespace'])) {
            $this->main_namespace = trim($config['main_namespace'], ' \\/'); 
        }
        $this->config = $config;
    }

    /**
     * 添加一个Struct对象
     * @param Struct $struct
     * @throws DOPException
     */
    public function addStruct(Struct $struct)
    {
        $namespace = $struct->getNamespace();
        $class_name = $struct->getClassName();
        $full_name = $namespace .'/'. $class_name;
        if (isset($this->struct_list[$full_name])) {
            throw new DOPException($this->fixErrorMsg('struct:' . $full_name . ' conflict'));
        }
        $this->struct_list[$full_name] = $struct;
    }

    /**
     * 加载某个struct
     * @param string $class_name
     * @return Struct|null
     * @throws DOPException
     */
    public function loadStruct($class_name)
    {
        if ($this->hasStruct($class_name)) {
            return $this->struct_list[$class_name];
        }
        //类名
        $struct_name = basename($class_name);
        if (empty($struct_name)) {
            throw new DOPException($this->fixErrorMsg('Can not loadStruct '. $class_name));
        }
        $xml_file = dirname($class_name). '.xml';
        $xml_protocol = $this->loadXmlProtocol($xml_file);
        $xml_protocol->queryStruct();
        if ($this->hasStruct($class_name)) {
            return $this->struct_list[$class_name];
        }
        return null;
    }

    /**
     * 加载指定的xml
     * @param string $xml_file 文件相对于base_path的路径
     * @return XmlProtocol
     * @throws DOPException
     */
    private function loadXmlProtocol($xml_file)
    {
        if (isset($this->xml_list[$xml_file])) {
            return $this->xml_list[$xml_file];
        }
        $protocol_obj = new XmlProtocol($this, $xml_file);
        $this->xml_list[$xml_file] = $protocol_obj;
        return $protocol_obj;
    }

    /**
     * 解析指定文件
     * @param string $file
     */
    public function parseFile($file)
    {
        $xml_protocol = $this->loadXmlProtocol($file);
        $xml_protocol->query();
    }

    /**
     * 是否存在某个Struct
     * @param string $fullName
     * @return bool
     */
    public function hasStruct($fullName)
    {
        return isset($this->struct_list[$fullName]);
    }

    /**
     * 把已经解析过的xml文件加入已经解析列表
     * @param string $file_name
     */
    public function pushXmlFile($file_name)
    {
        $this->xml_list[$file_name] = true;
    }

    /**
     * 获取所有的struct
     * @return array[Struct]
     */
    public function getAll()
    {
        return $this->struct_list;
    }

    /**
     * 设置当前的文档信息
     * @param string $doc_info
     */
    public function setCurrentProtocolDocInfo($doc_info)
    {
        if (!is_string($doc_info)) {
            throw new \InvalidArgumentException('Invalid doc_info'); 
        }
        $this->protocol_doc_info = $doc_info;
    }

    /**
     * 获取当前文档信息
     */
    public function getCurrentProtocolDocInfo()
    {
        return $this->protocol_doc_info;
    }

    /**
     * 补全报错信息
     * @param string $msg
     * @return string
     */
    public function fixErrorMsg($msg)
    {
        return $msg .' at '. $this->protocol_doc_info;
    }

    /**
     * 获取基础目录
     * @return string
     */
    public function getBasePath()
    {
        return $this->base_path;
    }

    /**
     * 获取文件生成目录
     * @return string
     */
    public function getBuildPath()
    {
        return $this->build_path;
    }

    /**
     * 获取main_namespace
     * @return string
     */
    public function getMainNameSpace()
    {
        return $this->main_namespace;
    }

    /**
     * 获取配置
     * @param string $key
     * @param null $default 如果不存在这个值的默认值
     * @return null
     */
    public function getConfig($key, $default = null)
    {
        if (!isset($this->config[$key])) {
            return $default;
        }
        return $this->config[$key];
    }
}
