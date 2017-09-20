<?php

namespace FFan\Dop;
use FFan\Dop\Build\BuildOption;
use FFan\Dop\Build\CoderBase;
use FFan\Dop\Build\Folder;
use FFan\Dop\Build\PluginBase;
use FFan\Dop\Build\Shader;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\ItemType;
use FFan\Dop\Protocol\ListItem;
use FFan\Dop\Protocol\MapItem;
use FFan\Dop\Protocol\Struct;
use FFan\Dop\Protocol\Protocol;
use FFan\Dop\Protocol\StructItem;
use FFan\Std\Common\Str as FFanStr;
use FFan\Std\Common\Utils as FFanUtils;

/**
 * Class Manager
 * @package FFan\Dop
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
     * @var CoderBase 当前Coder
     */
    private $current_coder;

    /**
     * @var array 虚拟目录对象
     */
    private $folder_list;

    /**
     * @var BuildOption 当前的build_opt
     */
    private $current_build_opt;

    /**
     * @var Shader[] 着色器列表
     */
    private $shader_list;

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
        $plugin_config = 'plugin:';
        foreach ($ini_config as $name => $value) {
            //代码生成配置
            if (0 === strpos($name, 'build')) {
                //使用main修正默认的build section
                if ('build' === $name) {
                    $name = 'build:main';
                }
                $this->build_section[$name] = $value;
            } //插件配置
            elseif (0 === strpos($name, $plugin_config)) {
                $plugin_name = substr($name, strlen($plugin_config));
                if (!empty($plugin_name)) {
                    $this->plugin_config[$plugin_name] = $value;
                }
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
        $tmp_struct = $this->getStruct($class_name);
        if (null !== $tmp_struct) {
            return $tmp_struct;
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
        return $this->getStruct($class_name);
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
     * 应用着色器
     */
    public function applyShader()
    {
        if (empty($this->shader_list)) {
            return;
        }
        /** @var Shader $shader */
        foreach ($this->shader_list as $shader) {
            $shader_name = $shader->getName();
            if (!$this->current_build_opt->isUseShader($shader_name)) {
                continue;
            }
            /**
             * @var string $path
             * @var Folder $folder
             */
            foreach ($this->folder_list as $path => $folder) {
                $shader->apply($folder);
            }
        }
    }

    /**
     * 生成代码
     * @param string $section 使用的配置section
     * @return bool
     */
    public function build($section = 'main')
    {
        $result = true;
        try {
            $this->initProtocol($section);
            $build_opt = $this->current_build_opt;
            $coder_class = $this->getCoderClass($build_opt->getCoderName());
            /** @var CoderBase $coder */
            $this->current_coder = $coder = new $coder_class($this, $build_opt);
            $coder->build();
            $this->applyShader();
            $this->saveFiles($build_opt->getFileOption());
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
     * @param int $option 各选项
     */
    private function saveFiles($option = 0)
    {
        if (empty($this->folder_list)) {
            return;
        }
        /**
         * @var string $path
         * @var Folder $folder
         */
        foreach ($this->folder_list as $path => $folder) {
            $folder->save($option);
        }
        $this->folder_list = null;
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
     * 添加着色器
     * @param Shader $shader
     */
    public function addShader(Shader $shader)
    {
        $this->shader_list[] = $shader;
    }

    /**
     * 初始化协议文件
     * @param $section
     * @return bool
     */
    public function initProtocol($section = 'main')
    {
        $name = 'build:' . $section;
        if (!isset($this->build_section[$name])) {
            $this->buildLogError('Build section ' . $name . ' not found!');
            return false;
        }
        $section_config = $this->build_section[$name];
        $build_opt = new BuildOption($section, $section_config, $this->config);
        $this->current_build_opt = $build_opt;
        $this->build_message = '';

        $this->init_protocol_flag = true;
        $file_list = $this->getAllFileList();
        $build_list = $file_list;
        $this->build_file_list = $build_list;
        //解析文件
        foreach ($build_list as $xml_file => $v) {
            $this->parseFile($xml_file);
        }

        $build_side = $build_opt->getBuildSide();

        //设置struct之间的引用关系
        /** @var Struct $struct */
        foreach ($this->struct_list as $struct) {
            $type = $struct->getType();
            if (Struct::TYPE_STRUCT === $type) {
                continue;
            }
            //如果是data类型 encode 和 decode 方法都要编译
            if (Struct::TYPE_DATA === $type) {
                $type = Struct::TYPE_REQUEST | Struct::TYPE_RESPONSE;
            }

            //如果 服务器 和 客户端都生成
            if (($build_side & BuildOption::SIDE_SERVER) && ($build_side & BuildOption::SIDE_CLIENT)) {
                $type = Struct::TYPE_REQUEST | Struct::TYPE_RESPONSE;
            }
            $this->setStructRef($struct, $type);
        }
        return true;
    }

    /**
     * //设置struct之间的引用关系
     * @param Struct $struct
     * @param int $ref_type
     */
    private function setStructRef($struct, $ref_type)
    {
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            $this->findItemRefStruct($item, $ref_type);
        }
    }

    /**
     * 找到字段中引用的struct
     * @param Item $item
     * @param int $ref_type
     */
    private function findItemRefStruct($item, $ref_type)
    {
        $item_type = $item->getType();
        switch ($item_type) {
            case ItemType::STRUCT:
                /** @var StructItem $item */
                $sub_struct = $item->getStruct();
                $sub_struct->addReferType($ref_type);
                $this->setStructRef($sub_struct, $ref_type);
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $this->findItemRefStruct($sub_item, $ref_type);
                break;
            case ItemType::MAP:
                /** @var MapItem $item */
                $value_item = $item->getValueItem();
                $this->findItemRefStruct($value_item, $ref_type);
                break;
        }
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
     * 设置缓存
     * @param string $key
     * @param mixed $value
     */
    public function setCache($key, $value)
    {
        $this->cache_data[$key] = $value;
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
     * 获取struct
     * @param string $full_name
     * @return Struct|null
     */
    public function getStruct($full_name)
    {
        return isset($this->struct_list[$full_name]) ? $this->struct_list[$full_name] : null;
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
        $full_class = '\FFan\Dop\Coder\\' . $coder_name . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new Exception('Unknown class name ' . $full_class);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['FFan\Dop\Build\CoderBase'])) {
            throw new Exception('Coder ' . $coder_name . ' must be implements of CoderBase');
        }
        $coder_instance_arr[$coder_name] = $full_class;
        return $full_class;
    }

    /**
     * 获取插件实例
     * @param string $plugin_name 插件名称
     * @return PluginBase|null
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
            return null;
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = 'FFan\Dop\Plugin\\' . $plugin_name . '\\' . $class_name;
        if (!class_exists($full_class)) {
            return null;
        }
        $parents = class_parents($full_class);
        if (!isset($parents['FFan\Dop\Build\PluginBase'])) {
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
        $path = FFanUtils::fixWithRuntimePath($path);
        if (!isset($this->folder_list[$path])) {
            $dop_folder = new Folder($path, $this);
            $this->folder_list[$path] = $dop_folder;
        }
        return $this->folder_list[$path];
    }

    /**
     * 获取当前的build opt
     * @return BuildOption
     */
    public function getCurrentBuildOpt()
    {
        return $this->current_build_opt;
    }
}
