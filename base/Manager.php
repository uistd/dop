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
use FFan\Dop\Protocol\StructItem;
use FFan\Dop\Schema\Cache;
use FFan\Dop\Schema\Protocol;
use FFan\Std\Common\Str as FFanStr;
use FFan\Std\Common\Utils as FFanUtils;
use FFan\Dop\Schema\Protocol as SchemaProtocol;

/**
 * Class Manager
 * @package FFan\Dop
 */
class Manager
{
    /**
     * @var Struct[] 所有的struct列表
     */
    private $struct_list = [];

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
     * @var array 生成代码的配置项
     */
    private $build_section;

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
     * @var Protocol
     */
    private $schema_protocol;

    /**
     * @var Cache 缓存
     */
    private $build_cache;

    /**
     * @var string[] 注册的packer
     */
    private $reg_packer_list;

    /**
     * 初始化
     * ProtocolManager constructor.
     * @param string $base_path 协议文件所在的目录
     * @param string $build_ini_content build_ini内容
     * @throws Exception
     */
    public function __construct($base_path, $build_ini_content = '')
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
        $this->initBuildOption($build_ini_content);
    }

    /**
     * 获取生成代码的参数
     * @param string $build_ini_content
     */
    private function initBuildOption($build_ini_content)
    {
        //如果 没有build_ini。那就尝试从build.ini加载
        if (!is_string($build_ini_content) || empty($build_ini_content)) {
            $ini_file = $this->base_path . 'build.ini';
            if (!is_file($ini_file)) {
                return;
            }
            $ini_config = parse_ini_file($ini_file, true);
        } else {
            $ini_config = parse_ini_string($build_ini_content, true);
        }
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
     * 生成缓存文件名
     * @param string $section
     * @return string
     */
    private function makeCacheFile($section)
    {
        $file = md5($this->base_path . $section);
        $path = FFanUtils::fixWithRuntimePath('build');
        return FFanUtils::joinFilePath($path, $file);
    }

    /**
     * 初始化协议文件
     * @param string $section
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
        $use_cache = $build_opt->getConfig('build_cache');
        $this->build_message = '';
        if ($use_cache) {
            $cache_key = md5(json_encode($section_config));
            $cache_file = $this->makeCacheFile($section);
        } else {
            $cache_key = $cache_file = 'not_exist';
        }
        $this->build_cache = new Cache($cache_key, $cache_file);
        $file_list = $this->getAllFileList();
        $build_list = $file_list;
        $this->build_file_list = $build_list;
        //解析文件
        foreach ($build_list as $xml_file => $v) {
            if ($build_opt->isIgnoreFile($xml_file)) {
                $this->buildLog('Ignore file:' . $xml_file);
                continue;
            }
            new Schema\File($this, $xml_file);
        }
        $this->loadRequireSchema();
        $this->schema_protocol = Protocol::getInstance($this);
        $this->schema_protocol->makeStruct();
        if ($use_cache) {
            //@todo 缓存功能未完成
            //$this->build_cache->save();
        }
        //如果忽略版本号，先整理版本号
        if ($build_opt->getConfig('ignore_version')) {
            $this->ignoreVersion();
        }

        //设置依赖关系
        foreach ($this->struct_list as $struct) {
            //echo 'check: ', $struct->getNamespace() .'/'. $struct->getClassName(), PHP_EOL;
            $all_item = $struct->getAllExtendItem();
            foreach ($all_item as $name => $item) {
                $this->structRequire($item, $struct);
            }
        }

        //如果忽略版本号，重新整理版本号
        if ($build_opt->getConfig('ignore_version')) {
            foreach ($this->struct_list as $struct) {
                $struct->resetNameSpaceIgnoreVersion();
            }
        }
        return true;
    }

    /**
     * 加载依赖的文件
     */
    private function loadRequireSchema()
    {
        $require_ns = Schema\File::getRequireNameSpace();
        if (empty($require_ns)) {
            return;
        }
        foreach ($require_ns as $ns => $doc) {
            Exception::pushStack('Load require '. $ns .'xml');
            if ('/' === $ns{0}) {
                $ns = substr($ns, 1);
            }
            new Schema\File($this, $ns . '.xml');
            Exception::popStack();
        }
        $this->loadRequireSchema();
    }

    /**
     * 忽略版本号处理
     */
    private function ignoreVersion()
    {
        //替换记录
        $replace_record = array();
        $class_map = array();
        //设置依赖关系
        foreach ($this->struct_list as $full_name => $struct) {
            $struct->getAllExtendItem();
            $namespace = $struct->getNamespace();
            $pos = strrpos($namespace, '_');
            $class_name = Struct::ignoreVersion($namespace) . '/' . $struct->getClassName();
            $class_map[$class_name][$full_name] = $struct;
            if (false === $pos) {
                continue;
            }
            $ver = substr($namespace, $pos);
            //如果 是以  _v2 类似的结束的
            if (!preg_match('#^_v(\d+)$#', $ver, $re)) {
                continue;
            }
            $version = $re[1];
            $struct->setVersion($version);
        }
        /** @var Struct[] $class_group */
        foreach ($class_map as $class_group) {
            //如果 这个类名只有一个，并且版本号是1，不处理
            if (1 === count($class_group) && 1 === current($class_group)->getVersion()) {
                continue;
            }
            $max_version = -1;
            $max_version_struct = null;
            /**
             * @var string $full_name
             * @var Struct $struct
             */
            foreach ($class_group as $full_name => $struct) {
                $version = $struct->getVersion();
                if ($version > $max_version) {
                    $max_version = $version;
                    $max_version_struct = $struct;
                }
            }
            if (null === $max_version_struct) {
                continue;
            }
            //记录下替换设置
            foreach ($class_group as $full_name => $struct) {
                unset($this->struct_list[$full_name]);
                $replace_record[$full_name] = $max_version_struct;
            }
            $new_full_name = Struct::ignoreVersion($struct->getNamespace()) . '/' . $struct->getClassName();
            $this->struct_list[$new_full_name] = $max_version_struct;
        }
        //遍历所有的struct，将item中有引用到struct的地方都检查一下，检查是否被替换了
        //设置依赖关系
        foreach ($this->struct_list as $struct) {
            $all_item = $struct->getAllExtendItem();
            foreach ($all_item as $name => $item) {
                $this->ignoreReplaceCheck($item, $replace_record);
            }
        }
    }

    /**
     * Struct 之间的依赖关系
     * @param Item $item
     * @param Struct[] $replace_record
     */
    private function ignoreReplaceCheck($item, $replace_record)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $sub_struct = $item->getStruct();
            $full_name = $sub_struct->getNamespace() . '/' . $sub_struct->getClassName();
            if (isset($replace_record[$full_name])) {
                $sub_struct = $replace_record[$full_name];
                $item->setStruct($sub_struct);
            }
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->ignoreReplaceCheck($item->getItem(), $replace_record);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->ignoreReplaceCheck($item->getValueItem(), $replace_record);
        }
    }

    /**
     * Struct 之间的依赖关系
     * @param Item $item
     * @param Struct $struct
     */
    private function structRequire($item, $struct)
    {
        $type = $item->getType();
        if (ItemType::STRUCT === $type) {
            /** @var StructItem $item */
            $sub_struct = $item->getStruct();
            $struct->addRequireStruct($sub_struct);
        } elseif (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $this->structRequire($item->getItem(), $struct);
        } elseif (ItemType::MAP === $type) {
            /** @var MapItem $item */
            $this->structRequire($item->getValueItem(), $struct);
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
        return $this->schema_protocol->getStruct($full_name);
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
     * @return Struct[]
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
        return $this->schema_protocol->getStructByNameSpace($file_name);
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
     * 是否存在某个插件
     * @param string $plugin_name
     * @return bool
     */
    public function hasPlugin($plugin_name)
    {
        return isset($this->plugin_list[ucfirst($plugin_name)]);
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
     * 注册一个packer
     * @param string $name
     * @param string $class_file
     */
    public function registerPacker($name, $class_file)
    {
        $this->reg_packer_list[$name] = $class_file;
    }

    /**
     * 获取注册的packer的类文件
     * @param string $name
     * @return null|string
     */
    public function getRegisterPacker($name)
    {
        return isset($this->reg_packer_list[$name]) ? $this->reg_packer_list[$name] : null;
    }

    /**
     * 获取代码生成器的类名
     * @param string $coder_name
     * @return string
     * @throws Exception
     */
    private function getCoderClass($coder_name)
    {
        $coder_name = FFanStr::camelName($coder_name);
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
        $plugin_name = FFanStr::camelName($plugin_name);
        if (isset($plugin_instance[$plugin_name])) {
            return $plugin_instance[$plugin_name];
        }
        if (!$this->hasPlugin($plugin_name)) {
            throw new Exception('Plugin '. $plugin_name .' not exist');
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
        $coder_name = FFanStr::camelName($coder_name);
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

    /**
     * @return SchemaProtocol
     */
    public function getProtocol()
    {
        return $this->schema_protocol;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->build_cache;
    }
}
