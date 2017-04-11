<?php
namespace ffan\dop;

use ffan\php\utils\Str as FFanStr;

/**
 * Class ProtocolManager
 * @package ffan\dop
 */
class ProtocolManager
{
    /**
     * 编译模板： php
     */
    const BUILD_TPL_PHP = 'php';

    /**
     * 编译模板：js
     */
    const BUILD_TPL_JS = 'js';

    /**
     * @var array 所有的struct列表
     */
    private $struct_list = [];

    /**
     * @var array 解析过的xml列表
     */
    private $xml_list = [];

    /**
     * @var array 所有的协议文件
     */
    private $all_file_list;

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
     * @var array 文件之间的依赖关系
     */
    private $require_map;

    /**
     * @var BuildCache 编译缓存
     */
    private $cache;

    /**
     * @var array 插件
     */
    private $plugin_list;

    /**
     * @var int 编译模板
     */
    private $build_tpl_type;

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
        //这里将 base_path 和 build path 写入config，为了做缓存需要
        $config['__base_path__'] = $base_path;
        $config['__build_path__'] = $build_path;
        $this->config = $config;
        $this->initPlugin();
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
     * 加载依赖的某个struct
     * @param string $class_name 依赖的class
     * @param string $current_xml 当前正在解析的xml文件
     * @return Struct|null
     * @throws DOPException
     */
    public function loadRequireStruct($class_name, $current_xml)
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
        if ('/' === $xml_file[0]) {
            $xml_file = substr($xml_file, 1);
        }
        $this->setRequireRelation($xml_file, $current_xml);
        $xml_protocol = $this->loadXmlProtocol($xml_file);
        $xml_protocol->queryStruct();
        if ($this->hasStruct($class_name)) {
            return $this->struct_list[$class_name];
        }
        return null;
    }

    /**
     * 设置xml之间的依赖关系
     * @param string $require_xml
     * @param string $current_xml
     */
    private function setRequireRelation($require_xml, $current_xml)
    {
        if (!isset($this->require_map[$require_xml])) {
            $this->require_map[$require_xml] = array();
        }
        $this->require_map[$require_xml][$current_xml] = true;
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
        $this->buildLog('Load ' . $xml_file);
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
     * 编译成PHP代码
     * @return string
     */
    public function buildPhp()
    {
        return $this->doBuild(self::BUILD_TPL_PHP);
    }

    /**
     * 编译日志
     * @param string $msg 消息内容
     * @param string $type 类型
     */
    public function buildLog($msg, $type = 'ok')
    {
        $content = '[' . $type . ']' . $msg . PHP_EOL;
        $this->build_message .= $content;
    }

    /**
     * 错误日志
     * @param string $msg 日志消息
     */
    public function buildLogError($msg)
    {
        $this->buildLog($msg, 'error');
    }

    /**
     * notice日志
     * @param string $msg 日志消息
     */
    public function buildLogNotice($msg)
    {
        $this->buildLog($msg, 'notice');
    }

    /**
     * 获取所有的文件列表
     * @return array
     */
    public function getAllFileList()
    {
        if (null !== $this->all_file_list) {
            return $this->all_file_list;
        }
        $file_list = array();
        $this->getBuildFileList($this->base_path, $file_list);
        $this->all_file_list = $file_list;
        return $file_list;
    }

    /**
     * 待编译的所有文件
     * @param int $build_tpl 编译模板类型
     * @return bool
     */
    private function doBuild($build_tpl)
    {
        $this->build_tpl_type = $build_tpl;
        $file_list = $this->getAllFileList();
        $this->build_message = '';
        $build_list = $this->filterCacheFile($file_list);
        $result = true;
        if (empty($build_list)) {
            $this->buildLog('没有需要编译的文件');
        } else {
            try {
                foreach ($build_list as $xml_file => $v) {
                    $this->parseFile($xml_file);
                }
                //检查所有的struct，如果是来自没有修改过的文件的，就标记为不用编译
                /** @var Struct $struct */
                foreach ($this->struct_list as $struct) {
                    $file = $struct->getFile();
                    if (!isset($build_list[$file])) {
                        $struct->setNeedBuild(false);
                    }
                }
                $this->generateFile($build_tpl);
                $this->buildLog('done!');
            } catch (DOPException $exception) {
                $msg = $exception->getMessage();
                $this->buildLogError($msg);
                $result = false;
            }
        }
        if ($result) {
            $this->saveCache($file_list, $this->require_map);
        }

        return $result;
    }

    /**
     * 过滤已经编译过缓存过的文件
     * @param array $file_list
     * @return array
     */
    private function filterCacheFile($file_list)
    {
        //不使用缓存
        if ($this->getConfig('disable_cache')) {
            return $file_list;
        }
        //加载一些缓存数据
        $cache = $this->getBuildCache();
        $cache_data = $cache->loadCache('build');
        if (!is_array($cache_data)) {
            return $file_list;
        }
        $this->require_map = $cache_data['require_map'];
        $file_build_time = $cache_data['build_time'];
        $new_file_list = array();
        //如果文件没有发生改变，就不编译
        foreach ($file_list as $xml_file => $modify_time) {
            if (isset($file_build_time[$xml_file]) && $file_build_time[$xml_file] === $modify_time) {
                $this->buildLogNotice($xml_file . ' no changes.');
                continue;
            }
            $new_file_list[$xml_file] = $modify_time;
        }
        //文件发生改变了，把依赖该文件的xml也找出来，所有依赖该文件的都要重新编译
        $relation_arr = array();
        foreach ($new_file_list as $xml_file => $modify_time) {
            if (!isset($this->require_map[$xml_file])) {
                continue;
            }
            foreach ($this->require_map[$xml_file] as $file => $v) {
                $relation_arr[$file] = $v;
            }
        }
        //将影响的文件合并入$new_file_list
        foreach ($relation_arr as $file => $v) {
            //文件已经不存在了
            if (!isset($file_list[$file])) {
                continue;
            }
            $this->buildLogNotice($file . ' required.');
            $new_file_list[$file] = $file_list[$file];
        }
        return $new_file_list;
    }

    /**
     * 保存缓存
     * @param array $file_time
     * @param array $require_map
     */
    private function saveCache($file_time, $require_map)
    {
        //不使用缓存
        if ($this->getConfig('disable_cache')) {
            return;
        }
        $cache_data = array(
            'require_map' => $require_map,
            'build_time' => $file_time
        );
        $cache = $this->getBuildCache();
        $cache->saveCache('build', $cache_data);
    }

    /**
     * 获取缓存实例
     * @return BuildCache
     */
    private function getBuildCache()
    {
        if (!$this->cache) {
            $this->cache = new BuildCache($this, $this->config);
        }
        return $this->cache;
    }

    /**
     * 获取编译的日志
     * @return string
     */
    public function getBuildLog()
    {
        return $this->build_message;
    }

    /**
     * 生成最终的文件
     * @param int $build_tpl 编译模板
     * @throws DOPException
     */
    private function generateFile($build_tpl)
    {
        switch ($build_tpl) {
            case self::BUILD_TPL_PHP:
                $build_obj = new PhpGenerator($this);
                break;
            case self::BUILD_TPL_JS:
                $build_obj = new JsGenerator($this);
                break;
            default:
                //尝试使用自定义类
                $class_name = $this->getConfig('generator_class');
                $build_obj = new $class_name($this);
                if (!$build_obj instanceof DOPGenerator) {
                    throw new DOPException('无法使用自定义生成类：' . $class_name);
                }
                throw new DOPException('不支持的编译模板:' . $build_tpl);
                break;
        }
        $build_obj->generate();
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
        $base_len = strlen($this->base_path);
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
                $xml_file = substr($file_path, $base_len + 1);
                $file_list[$xml_file] = filemtime($file_path);
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

    /**
     * 初始化插件
     * @throws DOPException
     */
    private function initPlugin()
    {
        $plugin_list = $this->getConfig('plugin');
        if (!is_array($plugin_list)) {
            return;
        }
        foreach ($plugin_list as $name => $plugin_conf) {
            switch ($name) {
                case 'validator':
                    $plugin = new ValidatorPlugin($this);
                    break;
                case 'mock':
                    $plugin = new MockPlugin($this);
                    break;
                default:
                    throw new DOPException('Plugin ' . $name . ' not recognized!');
            }
            $this->plugin_list[$name] = $plugin;
        }
    }

    /**
     * 获取插件列表
     * @return array|null
     */
    public function getPluginList()
    {
        return $this->plugin_list;
    }

    /**
     * 获取当前编译的模板类型
     * @return string
     */
    public function getBuildTplType()
    {
        return $this->build_tpl_type;
    }
}
