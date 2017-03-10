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
     * @var string 编译结果
     */
    private $build_message;

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
            throw new DOPException('Protocol path:' . $base_path . ' not exist!');
        }
        if (!is_readable($base_path)) {
            throw new DOPException('Protocol path:' . $base_path . ' is not readable');
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
        $full_name = $namespace . '/' . $class_name;
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
            throw new DOPException($this->fixErrorMsg('Can not loadStruct ' . $class_name));
        }
        $xml_file = dirname($class_name) . '.xml';
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
     * 编译整个目录
     */
    public function build()
    {
        $file_list = array();
        $this->build_message = '';
        $this->getBuildFileList($this->base_path, $file_list);
        if ($this->getConfig('build_cache')) {
            $this->cacheFilter($file_list);
        }
        if (empty($file_list)) {
            $this->buildLog('无需要编译的文件');
        } else {
            $this->doBuild($file_list);
        }
        //返回提示消息内容
        return $this->build_message;
    }

    /**
     * 通过缓存过滤掉已经不需要编译的xml文件
     * @param array $file_list 整个目录所有列表
     */
    private function cacheFilter(&$file_list)
    {
        $this->buildLogNotice('检查编译缓存');
        $path = FFanUtils::fixWithRuntimePath('');
        $cache_file = FFanUtils::joinFilePath($path, 'dop.cache.php');
        if (!is_file($cache_file)) {
            $this->buildLogNotice('缓存文件不存在');
            return;
        }
        $cache_str = file_get_contents($cache_file);
        if (false === $cache_str) {
            return;
        }
        $cache_arr = unserialize($cache_str);
        //如果不是数组 或者 没有签名这个key
        if (!is_array($cache_arr) || !isset($cache_arr['sign'])) {
            $this->buildLogError('缓存数据错误');
            return;
        }
        $sign_str = $cache_arr['sign'];
        unset($cache_arr['sign']);
        //缓存签名不同，不可用
        if ($sign_str !== $this->signCacheArr($cache_arr)) {
            $this->buildLogError('缓存签名出错');
            return;
        }
        //缓存的目录和当前的base_path不同
        if ($cache_arr['base_path'] !== $this->base_path) {
            $this->buildLogNotice('编译目录改变, 缓存不可用');
            return;
        }
        //生成文件的目标文件夹不同
        if ($cache_arr['build_path'] !== $this->build_path) {
            $this->buildLogNotice('生成文件目录改变, 缓存不可用');
            return;
        }
        //配置文件发生变化了
        if ($cache_arr['config'] !== $this->configMd5()) {
            $this->buildLogNotice('编译配置改变, 缓存不可用');
            return;
        }
        $build_cache_list = $cache_arr['build_file_arr'];
        foreach ($file_list as $key => $file) {
            //不再缓存里
            if (!isset($build_cache_list[$file])) {
                continue;
            }
            $last_time = filemtime($file);
            if ($build_cache_list[$file] === $last_time) {
                $this->buildLog($file . ' 编译结果已经缓存, 无需编译');
                unset($file_list[$key]);
            }
        }
    }

    /**
     * 编译日志
     * @param string $msg 消息内容
     * @param string $type 类型
     */
    private function buildLog($msg, $type = 'ok')
    {
        $content = '[' . $type . ']' . $msg . PHP_EOL;
        $this->build_message .= $content;
    }

    /**
     * 错误日志
     * @param string $msg 日志消息
     */
    private function buildLogError($msg)
    {
        $this->buildLog($msg, 'error');
    }

    /**
     * 普通日志
     * @param string $msg 日志消息
     */
    private function buildLogNotice($msg)
    {
        $this->buildLog($msg, 'notice');
    }

    /**
     * 待编译的所有文件
     * @param array $file_list
     */
    private function doBuild($file_list)
    {
        if (empty($file_list)) {
            return;
        }
    }

    /**
     * 获取当前配置文件的md5码
     * @return string
     */
    private function configMd5()
    {
        return md5(serialize($this->config));
    }

    /**
     * 缓存签名，用于校验缓存值是否有效
     * @param array $cache_arr
     * @return string
     */
    private function signCacheArr($cache_arr)
    {
        return md5(serialize($cache_arr));
    }

    /**
     * 获取所有需要编译的文件列表
     * @param string $dir 目录名
     * @param array $file_list 存结果的数组
     */
    private function getBuildFileList($dir, &$file_list)
    {
        $dir_handle = opendir($dir);
        if (false === $dir_handle) {
            return;
        }
        while (false != ($file = readdir($dir_handle))) {
            if ('.' === $file[0]) {
                continue;
            }
            $file_path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file_path)) {
                if (!FFanStr::isValidVarName($file)) {
                    $this->buildLogError($file_list . ' 目录名:' . $file . '不能用作命名空间');
                    continue;
                }
                $this->getBuildFileList($file_path, $file_list);
            } else {
                if ('.xml' !== substr(strtolower($file), -4)) {
                    $this->buildLogError($file_list . '非XML文件');
                    continue;
                }
                $tmp_name = basename($file, '.xml');
                if (!FFanStr::isValidVarName($tmp_name)) {
                    $this->buildLogError($file_list . ' 目录名:' . $tmp_name . '不能用作命名空间');
                    continue;
                }
                $file_list[] = $file_path;
            }
        }
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
        return $msg . ' at ' . $this->protocol_doc_info;
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
