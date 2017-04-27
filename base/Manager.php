<?php

namespace ffan\dop;
require_once 'Common.php';
use ffan\dop\build\BuildCache;
use ffan\dop\build\BuildOption;
use ffan\dop\build\CoderBase;
use ffan\dop\build\Folder;
use ffan\dop\build\PluginBase;
use ffan\dop\protocol\Struct;
use ffan\dop\protocol\Protocol;
use ffan\php\utils\Str as FFanStr;
use ffan\php\utils\Utils as FFanUtils;

/**
 * Class Manager
 * @package ffan\dop
 */
class Manager
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
     * @var array 所有的协议文件
     */
    private $all_file_list;

    /**
     * @var string 协议基础路径
     */
    private $base_path;

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
     * @var array 缓存数据
     */
    private $cache_data;

    /**
     * @var array 代码生成器
     */
    private $coder_list;

    /**
     * @var array 插件
     */
    private $plugin_list;

    /**
     * @var array 插件的配置
     */
    private $plugin_config;

    /**
     * @var array 本次编译的文件
     */
    private $build_file_list;

    /**
     * @var array 每个文件的struct
     */
    private $file_struct_list = [];

    /**
     * @var array 生成代码的配置项
     */
    private $build_section;

    /**
     * @var bool 是否解析过协议文件
     */
    private $init_protocol_flag = false;

    /**
     * @var string 缓存的文件名
     */
    private $cache_name;

    /**
     * @var CoderBase 当前Coder
     */
    private $current_coder;

    /**
     * @var array 虚拟目录对象
     */
    private $folder_list;

    /**
     * 初始化
     * ProtocolManager constructor.
     * @param string $base_path 协议文件所在的目录
     * @throws Exception
     */
    public function __construct($base_path)
    {
        $base_path = FFanUtils::fixWithRootPath($base_path);
        if (!is_dir($base_path)) {
            throw new Exception('Protocol path:' . $base_path . ' not exist!');
        }
        if (!is_readable($base_path)) {
            throw new Exception('Protocol path:' . $base_path . ' is not readable');
        }
        $this->base_path = $base_path;
        $this->initCoder();
        $this->initPlugin();
        $this->initBuildOption();
    }

    /**
     * 获取生成代码的参数
     */
    private function initBuildOption()
    {
        $ini_file = $this->base_path . 'build.ini';
        if (!is_file($ini_file)) {
            return;
        }
        $ini_config = parse_ini_file($ini_file, true);
        //公共配置
        if (!empty($ini_config['public']) && is_array($ini_config['public'])) {
            $this->config = $ini_config['public'];
        }
        //自定义代码生成器
        if (!empty($ini_config['coder']) && is_array($ini_config['coder'])) {
            foreach ($ini_config['coder'] as $name => $path) {
                $this->registerCoder($name, $path);
            }
        }
        //自定义插件
        if (!empty($ini_config['plugin']) && is_array($ini_config['plugin'])) {
            foreach ($ini_config['plugin'] as $name => $path) {
                $this->registerPlugin($name, $path);
            }
        }
        foreach ($ini_config as $name => $value) {
            //代码生成配置
            if (0 === strpos($name, 'build')) {
                //使用main修正默认的build section
                if ('build' === $name) {
                    $name = 'build:main';
                }
                $this->build_section[$name] = $value;
            }
            //插件配置
            elseif (0 === strpos($name, 'plugin:')) {
                $plugin_name = substr($name, strlen('plugin:'));
                $this->plugin_config[$plugin_name] = $value;
            }
        }
    }

    /**
     * 添加一个Struct对象
     * @param Struct $struct
     * @throws Exception
     */
    public function addStruct(Struct $struct)
    {
        $namespace = $struct->getNamespace();
        $class_name = $struct->getClassName();
        $full_name = $namespace . '/' . $class_name;
        if (isset($this->struct_list[$full_name])) {
            throw new Exception('struct:' . $full_name . ' conflict');
        }
        $this->struct_list[$full_name] = $struct;
        $file = $struct->getFile();
        if (!isset($this->file_struct_list[$file])) {
            $this->file_struct_list[$file] = array();
        }
        $this->file_struct_list[$file][] = $struct;
    }

    /**
     * 加载依赖的某个struct
     * @param string $class_name 依赖的class
     * @param string $current_xml 当前正在解析的xml文件
     * @return Struct|null
     * @throws Exception
     */
    public function loadRequireStruct($class_name, $current_xml)
    {
        if ($this->hasStruct($class_name)) {
            return $this->struct_list[$class_name];
        }
        //类名
        $struct_name = basename($class_name);
        if (empty($struct_name)) {
            throw new Exception('Can not loadStruct ' . $class_name);
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
     * @return Protocol
     * @throws Exception
     */
    private function loadXmlProtocol($xml_file)
    {
        if (isset($this->xml_list[$xml_file])) {
            return $this->xml_list[$xml_file];
        }
        $this->buildLog('Load ' . $xml_file);
        $protocol_obj = new Protocol($this, $xml_file);
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
     * 生成代码
     * @param string $section 使用的配置section
     * @return bool
     */
    public function build($section = 'main')
    {
        $name = 'build:' . $section;
        if (!isset($this->build_section[$name])) {
            $this->buildLogError('Build section ' . $name . ' not found!');
            return false;
        }
        $section_config = $this->build_section[$name];
        $build_opt = new BuildOption($section, $section_config, $this->config);
        $this->build_message = '';
        $result = true;
        try {
            if (!$this->init_protocol_flag) {
                $this->initProtocol();
            }
            $coder_class = $this->getCoderClass($build_opt->getCoderName());
            /** @var CoderBase $coder */
            $this->current_coder = $coder = new $coder_class($this, $build_opt);
            $coder->build();
            $this->saveFiles();
            $this->buildLog('done!');
        } catch (Exception $exception) {
            $msg = $exception->getMessage();
            $this->buildLogError($msg);
            return false;
        }
        $this->current_coder = null;
        return $result;
    }

    /**
     * 保存生成的所有文件
     */
    private function saveFiles()
    {
        if (empty($this->folder_list)) {
            return;
        }
        /**
         * @var string $path
         * @var Folder $folder
         */
        foreach ($this->folder_list as $path => $folder) {
            $folder->save();
        }
        $this->folder_list = null;
    }

    /**
     * 是否缓存协议解析结果
     * @return bool
     */
    private function isCacheProtocol()
    {
        return !empty($this->config['cache_protocol']);
    }

    /**
     * 获取当前的Coder
     * @return CoderBase | null
     */
    public function getCurrentCoder()
    {
        return $this->current_coder;
    }

    /**
     * 初始化协议文件
     */
    private function initProtocol()
    {
        $this->init_protocol_flag = true;
        $file_list = $this->getAllFileList();
        $use_flag = $this->isCacheProtocol();
        if ($use_flag) {
            $this->initCache();
            $build_list = $this->filterCacheFile($file_list);
        } else {
            $build_list = $file_list;
        }
        $this->build_file_list = $build_list;
        //解析文件
        foreach ($build_list as $xml_file => $v) {
            $this->parseFile($xml_file);
        }
        //从缓存中将struct补全
        if ($use_flag) {
            $this->loadStructFromCache($build_list, $file_list);
        }
        /** @var Struct $struct */
        foreach ($this->struct_list as $struct) {
            $file = $struct->getFile();
            //如果是来自没有修改过的文件的，就标记为来自缓存
            if (!isset($build_list[$file])) {
                $struct->setCacheFlag(true);
            }
        }
        //保存缓存文件
        if ($use_flag) {
            $this->setCache('require_map', $this->require_map);
            $this->setCache('build_time', $file_list);
            $this->setCache('struct_list', $this->struct_list);
            $this->saveCache();
        }
        Exception::setAppendMsg('Build files');
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
     * 从缓存中加载 struct
     * @param array $build_list 本次解析过的协议文件
     * @param array $file_list 所有的协议文件
     */
    private function loadStructFromCache($build_list, $file_list)
    {
        $cache_struct = $this->getCache('struct_list');
        if (!$cache_struct) {
            return;
        }
        /**
         * @var string $name
         * @var Struct $tmp_struct
         */
        foreach ($cache_struct as $name => $tmp_struct) {
            //已经有了
            if ($this->hasStruct($name)) {
                continue;
            }
            $file = $tmp_struct->getFile();
            //如果struct所在的文件，本次编译了，表示 这个struct已经不存在了
            if (isset($build_list[$file])) {
                $this->buildLog($tmp_struct->getClassName() . ' missing');
                continue;
            }
            //找不到文件
            if (!isset($file_list[$file])) {
                $this->buildLog($tmp_struct->getClassName() . ' missing');
                continue;
            }
            $this->addStruct($tmp_struct);
        }
    }

    /**
     * 过滤已经编译过缓存过的文件
     * @param array $file_list
     * @return array
     */
    private function filterCacheFile($file_list)
    {
        //加载一些缓存数据
        $require_map = $this->getCache('require_map');
        $file_build_time = $this->getCache('build_time');
        if (!is_array($file_build_time) || !is_array($require_map)) {
            return $file_list;
        }
        $this->require_map = $require_map;
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
     */
    private function saveCache()
    {
        if (!$this->cache) {
            return;
        }
        $this->cache->saveCache($this->cache_name, $this->cache_data);
    }

    /**
     * 设置缓存
     * @param string $key
     * @param mixed $value
     */
    public function setCache($key, $value)
    {
        $this->cache_data[$key] = $value;
    }

    /**
     * 从缓存文件中获取指定值
     * @param string $key
     * @return mixed|null
     */
    private function getCache($key)
    {
        if (!isset($this->cache_data[$key])) {
            return null;
        }
        return $this->cache_data[$key];
    }

    /**
     * 初始化缓存
     */
    private function initCache()
    {
        if ($this->cache) {
            return;
        }
        $ini_file = $this->base_path . 'build.ini';
        $sign_key = md5(serialize(file_get_contents($ini_file)) . $this->base_path);
        $this->cache_name = 'build.' . substr(md5($this->base_path), -8);
        $cache_path = FFanUtils::fixWithRuntimePath('dop ');
        $this->cache = new BuildCache($this, $sign_key, $cache_path);
        $cache_data = $this->cache->loadCache($this->cache_name);
        if (!is_array($cache_data)) {
            $this->cache_data = array();
        } else {
            $this->cache_data = $cache_data;
        }
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
     * 获取所有需要编译的文件列表
     * @param string $dir 目录名
     * @param array $file_list 存结果的数组
     */
    private function getNeedBuildFile($dir, &$file_list)
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
                $this->getNeedBuildFile($file_path, $file_list);
            } else {
                if ('.xml' !== substr(strtolower($file), -4)) {
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
     * 获取基础目录
     * @return string
     */
    public function getBasePath()
    {
        return $this->base_path;
    }

    /**
     * 初始化插件
     * @throws Exception
     */
    private function initPlugin()
    {
        $base_dir = dirname(__DIR__) . '/plugin/';
        $folder_list = $this->getAllSubFolder($base_dir);
        foreach ($folder_list as $name) {
            $this->registerPlugin($name, $base_dir . $name);
        }
    }

    /**
     * 初始化代码生器对象
     */
    private function initCoder()
    {
        $base_dir = dirname(__DIR__) . '/coder/';
        $folder_list = $this->getAllSubFolder($base_dir);
        foreach ($folder_list as $name) {
            $this->registerCoder($name, $base_dir . $name);
        }
    }

    /**
     * 获取所有的子文件夹
     * @param string $dir_name 目录名称
     * @return array
     */
    private function getAllSubFolder($dir_name)
    {
        $result = array();
        $len = strlen($dir_name);
        if (DIRECTORY_SEPARATOR !== $dir_name[$len - 1]) {
            $dir_name .= DIRECTORY_SEPARATOR;
        }
        $dir_handle = opendir($dir_name);
        while (false != ($file = readdir($dir_handle))) {
            $tmp_name = $dir_name . $file;
            if (!is_dir($tmp_name) || '.' === $file{0}) {
                continue;
            }
            $result[] = $file;
        }
        return $result;
    }

    /**
     * 获取插件列表
     * @return array
     */
    public function getPluginList()
    {
        $result = array();
        foreach ($this->plugin_list as $name => $path) {
            $result[$name] = $this->getPlugin($name);
        }
        return $result;
    }

    /**
     * 获取所有的struct
     * @return array[Struct]
     */
    public function getAllStruct()
    {
        return $this->struct_list;
    }

    /**
     * 获取某个文件里的所有struct
     * @param string $file_name
     * @return array
     */
    public function getStructByFile($file_name)
    {
        if (!isset($this->file_struct_list[$file_name])) {
            return array();
        }
        return $this->file_struct_list[$file_name];
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
        $this->getNeedBuildFile($this->base_path, $file_list);
        $this->all_file_list = $file_list;
        return $file_list;
    }

    /**
     * 获取所有的文件列表
     */
    public function getBuildFileList()
    {
        return $this->build_file_list;
    }

    /**
     * 注册一个代码生成器
     * @param string $name 代码生成器名称
     * @param string $base_path 基础目录
     * @throws Exception
     */
    public function registerCoder($name, $base_path)
    {
        if (isset($this->coder_list[$name])) {
            throw new Exception('Coder ' . $name . ' has exist!');
        }
        $this->coder_list[$name] = FFanUtils::fixPath($base_path);
    }

    /**
     * 注册一个插件
     * @param string $name 插件名称
     * @param string $base_path 插件基础路径
     * @throws Exception
     */
    public function registerPlugin($name, $base_path)
    {
        if (isset($this->plugin_list[$name])) {
            throw new Exception('Coder ' . $name . ' has exist!');
        }
        $this->plugin_list[$name] = FFanUtils::fixPath($base_path);
    }

    /**
     * 获取一个插件的主目录
     * @param string $plugin_name
     * @return string
     * @throws Exception
     */
    public function getPluginMainPath($plugin_name)
    {
        if (!isset($this->plugin_list[$plugin_name])) {
            throw new Exception('Plugin ' . $plugin_name . ' not found');
        }
        return $this->plugin_list[$plugin_name];
    }

    /**
     * 获取插件配置
     * @param $plugin_name
     * @return null|array
     */
    public function getPluginConfig($plugin_name)
    {
        if (!isset($this->plugin_config[$plugin_name])) {
            return null;
        }
        return $this->plugin_config[$plugin_name];
    }

    /**
     * 获取代码生成器的类名
     * @param string $coder_name
     * @return string
     * @throws Exception
     */
    private function getCoderClass($coder_name)
    {
        static $coder_instance_arr = [];
        if (isset($coder_instance_arr[$coder_name])) {
            return $coder_instance_arr[$coder_name];
        }
        if (!isset($this->coder_list[$coder_name])) {
            throw new Exception('Coder ' . $coder_name . ' not found!');
        }
        $base_path = $this->coder_list[$coder_name];
        $class_name = 'Coder';
        $file = $base_path . '/' . $class_name . '.php';
        if (!is_file($file)) {
            throw new Exception('Can not find coder file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = '\ffan\dop\coder\\' . $coder_name . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new Exception('Unknown class name ' . $full_class);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['ffan\dop\build\CoderBase'])) {
            throw new Exception('Coder ' . $coder_name . ' must be implements of CoderBase');
        }
        $coder_instance_arr[$coder_name] = $full_class;
        return $full_class;
    }

    /**
     * 获取插件实例
     * @param string $plugin_name 插件名称
     * @return PluginBase
     * @throws Exception
     */
    public function getPlugin($plugin_name)
    {
        static $plugin_instance = [];
        if (isset($plugin_instance[$plugin_name])) {
            return $plugin_instance[$plugin_name];
        }
        $plugin_dir = $this->plugin_list[$plugin_name];
        $class_name = 'Plugin';
        $file = FFanUtils::joinFilePath($plugin_dir, $class_name . '.php');
        if (!is_file($file)) {
            throw new Exception('Plugin class file: ' . $file . ' not found!');
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = 'ffan\dop\plugin\\' . $plugin_name . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new Exception('Class "' . $full_class . '" not found in file:' . $file);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['ffan\dop\build\PluginBase'])) {
            throw new Exception('Plugin ' . $full_class . ' must be implements of PluginBase');
        }
        $plugin_instance[$plugin_name] = new $full_class($this, $plugin_name);
        return $plugin_instance[$plugin_name];
    }

    /**
     * 获取一个coder的基础目录
     * @param string $coder_name
     * @return string
     * @throws Exception
     */
    public function getCoderPath($coder_name)
    {
        if (!isset($this->coder_list[$coder_name])) {
            throw new Exception('Coder "' . $coder_name . '" is unregistered!');
        }
        return $this->coder_list[$coder_name];
    }

    /**
     * 获取一个虚拟目录
     * @param string $path
     * @return Folder
     */
    public function getFolder($path)
    {
        $path = Folder::checkPathName($path);
        $path = FFanUtils::fixWithRuntimePath($path);
        if (!isset($this->folder_list[$path])) {
            $dop_folder = new Folder($path, $this);
            $this->folder_list[$path] = $dop_folder;
        }
        return $this->folder_list[$path];
    }
}
