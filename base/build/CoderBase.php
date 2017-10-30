<?php

namespace FFan\Dop\Build;

use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Dop\Protocol\Item;
use FFan\Dop\Protocol\Struct;
use FFan\Std\Common\ConfigBase;
use FFan\Std\Common\Utils as FFanUtils;
use FFan\Std\Common\Str as FFanStr;


/**
 * Class CoderBase 生成器基类
 * @package FFan\Dop\Build
 */
abstract class CoderBase extends ConfigBase
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var BuildOption
     */
    protected $build_opt;

    /** @var string 当前语言名称 */
    protected $coder_name;

    /**
     * @var array 生成打包， 解角代码的对象
     */
    private $pack_instance_arr;

    /**
     * @var array 注册的打包器
     */
    private $reg_packer;

    /**
     * @var string 生成代码的基础目录
     */
    private $build_base_path;

    /**
     * @var PackerBase[] 所有加载的packer
     */
    private $packer_list;

    /**
     * @var CoderBase
     */
    protected $parent;

    /**
     * @var string extends 关键字
     */
    protected $extends_flag = ' extends ';

    /**
     * @var string implements 关键字
     */
    protected $implements_flag = ' implements ';

    /**
     * @var string 多个 extends 的连接字符串
     */
    protected $extends_join_char = ',';

    /**
     * @var string 多个 implements 的连接字符串
     */
    protected $implements_join_char = ',';

    /**
     * 字段名转换对照表
     * @var array
     */
    protected static $name_convert_map;

    /**
     * CoderBase constructor.
     * @param Manager $manager
     * @param BuildOption $build_opt
     * @param CoderBase $parent 父级coder
     */
    public function __construct(Manager $manager, BuildOption $build_opt, CoderBase $parent = null)
    {
        $this->manager = $manager;
        $this->build_opt = $build_opt;
        $this->coder_name = $build_opt->getCoderName();
        $this->build_base_path = $build_opt->getBuildPath();
        $this->initConfig($build_opt->getSectionConf());
        $this->parent = $parent;
    }

    /**
     * 生成文件
     */
    public function build()
    {
        Exception::setAppendMsg('Build common file');
        $this->buildCommonCode();
        $this->buildByStruct();
        $this->buildByXmlFile();
        $this->pluginBuild();
    }

    /**
     * 返回生成目录
     * @return Folder
     */
    public function getFolder()
    {
        return $this->manager->getFolder($this->build_base_path);
    }

    /**
     * 生成struct文件
     */
    private function buildByStruct()
    {
        $this->structIterator(array($this, 'codeByStruct'));
    }

    /**
     * 按XML协议文件生成文件
     */
    private function buildByXmlFile()
    {
        $this->xmlFileIterator(array($this, 'codeByXml'));
    }

    /**
     * 按struct迭代
     * @param callable $callback
     * @param bool $ignore_cache 是否忽略缓存
     */
    public function structIterator(callable $callback, $ignore_cache = false)
    {
        $all_struct = $this->manager->getAllStruct();
        $all_require_struct = array();
        foreach ($all_struct as $struct) {
            if ($struct->loadFromCache() && !$ignore_cache) {
                continue;
            }
            $struct_type = $struct->getType();
            if (!$this->build_opt->hasBuildProtocol($struct_type)) {
                continue;
            }
            //第一次不处理 struct  model
            if ($struct_type === Struct::TYPE_STRUCT) {
                continue;
            }
            $this->loadAllRequireStruct($all_require_struct, $struct);
            call_user_func($callback, $struct);
        }
        //生成依赖的struct
        /**
         * @var int $id
         * @var Struct $req_struct
         */
        foreach ($all_require_struct as $id => $req_struct) {
            call_user_func($callback, $req_struct);
        }
    }

    /**
     * 加载所有依赖的struct
     * @param array $all_require_struct
     * @param Struct $struct
     */
    private function loadAllRequireStruct(&$all_require_struct, $struct)
    {
        $require_struct = $struct->getRequireStruct();
        foreach ($require_struct as $id => $req_struct) {
            if (isset($all_require_struct[$id])) {
                continue;
            }
            $all_require_struct[$id] = $req_struct;
            $this->loadAllRequireStruct($all_require_struct, $req_struct);
        }
    }

    /**
     * 按XML文件迭代
     * @param callable $call_back
     */
    public function xmlFileIterator(callable $call_back)
    {
        $file_list = $this->manager->getBuildFileList();
        foreach ($file_list as $file => $t) {
            $struct_list = $this->manager->getStructByFile($file);
            $file_name = substr($file, 0, strlen('.xml') * -1);
            call_user_func_array($call_back, array($file_name, $struct_list));
        }
    }

    /**
     * 生成插件内容
     */
    private function pluginBuild()
    {
        $plugin_coder_arr = $this->getPluginCoder();
        /**
         * @var string $name
         * @var PluginCoderBase $plugin_coder
         */
        foreach ($plugin_coder_arr as $name => $plugin_coder) {
            $plugin_append_msg = 'Build plugin code: ' . $name;
            Exception::setAppendMsg($plugin_append_msg);
            $plugin_coder->buildCode();
        }
    }

    /**
     * 获取插件代码生成器
     * @return array
     */
    private function getPluginCoder()
    {
        $result = array();
        $plugin_list = $this->manager->getPluginList();
        /**
         * @var string $name
         * @var PluginBase $plugin
         */
        foreach ($plugin_list as $name => $plugin) {
            if (!$this->build_opt->isUsePlugin($name)) {
                continue;
            }
            $plugin_coder = $plugin->getPluginCoder($this->coder_name);
            if (null === $plugin_coder) {
                continue;
            }
            $result[$name] = $plugin_coder;
        }
        return $result;
    }

    /**
     * 生成通用的文件
     * @return void
     */
    public function buildCommonCode()
    {
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     */
    public function codeByStruct($struct)
    {
    }

    /**
     * 按XML文件生成代码
     * @param string $xml_file
     * @param array $ns_struct 该命名空间下所有的struct
     * @return void
     */
    public function codeByXml($xml_file, $ns_struct)
    {
    }

    /**
     * 生成 数据打包， 解包方法
     * @param FileBuf $file_buf
     * @param Struct $struct
     */
    protected function packMethodCode($file_buf, $struct)
    {
        $packer_object_arr = $this->getPackerList();
        /**
         * @var string $name
         * @var PackerBase $packer
         */
        foreach ($packer_object_arr as $name => $packer) {
            Exception::setAppendMsg('Build packer ' . $name);
            $this->writePackCode($struct, $file_buf, $packer);
        }
    }

    /**
     * 获取所有的packer，包括依赖的
     * @param array $packer_arr
     * @param PackerBase[] $packer_object_arr
     * @param int $side_code
     * @param PackerBase[] $load_arr 本次加载的packer
     */
    private function getAllPackerObject($packer_arr, &$packer_object_arr, $side_code = 0, &$load_arr = array())
    {
        foreach ($packer_arr as $packer_name) {
            if (0 === $side_code) {
                $side_code = $this->build_opt->getBuildSide($packer_name);
            }
            if (isset($packer_object_arr[$packer_name])) {
                $packer_object_arr[$packer_name]->setCodeSide($side_code);
                continue;
            }
            $packer_object = $this->getPackInstance($packer_name);
            if (null === $packer_object) {
                $this->manager->buildLogError('Can not found packer of ' . $packer_name);
                continue;
            }
            $packer_object->setCodeSide($side_code);
            $packer_object_arr[$packer_name] = $packer_object;
            $load_arr[] = $packer_object;
        }
        //再检查 这些packer是否依赖其它 packer
        foreach ($packer_arr as $packer_name) {
            $packer_object = $packer_object_arr[$packer_name];
            //依赖的其它packer
            $require_packer = $packer_object->getRequirePacker();
            if (empty($require_packer)) {
                continue;
            }
            /** @var PackerBase[] $new_load_packer */
            $new_load_packer = array();
            $this->getAllPackerObject($require_packer, $packer_object_arr, $side_code, $new_load_packer);
            foreach ($new_load_packer as $tmp_packer) {
                $tmp_packer->setMainPacker($packer_object);
            }
        }
    }

    /**
     * 获取所有的packer
     * @return array
     */
    private function getPackerList()
    {
        if (null !== $this->packer_list) {
            return $this->packer_list;
        }
        $packer_list = $this->build_opt->getPacker();
        $this->packer_list = array();
        $this->getAllPackerObject($packer_list, $this->packer_list);
        foreach ($this->packer_list as $name => $packer) {
            //如果不是主packer，不检查 is_extra 属性
            if (null !== $packer->getMainPacker()) {
                continue;
            }
            $packer->setExtraFlag($this->build_opt->isPackerExtra($name));
        }
        return $this->packer_list;
    }

    /**
     * 是否存在某个packer
     * @param $packer_name
     * @return bool
     */
    public function hasPacker($packer_name)
    {
        $packers = $this->getPackerList();
        return isset($packers[$packer_name]);
    }

    /**
     * 写入pack代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     * @param PackerBase $packer
     * @throws Exception
     */
    private function writePackCode($struct, FileBuf $file_buf, PackerBase $packer)
    {
        $code_buf = $file_buf->getBuf(FileBuf::METHOD_BUF);
        if (null === $code_buf) {
            return;
        }
        $packer->setFileBuf($file_buf);
        $packer->build();
        $struct_type = $struct->getType();
        $pack_name = $packer->getMainPackerName();
        $build_method = 0;
        //如果是 struct
        if (Struct::TYPE_STRUCT === $struct_type) {
            if ($struct->hasPackerMethod($pack_name, PackerBase::METHOD_PACK)) {
                $build_method |= PackerBase::METHOD_PACK;
            }
            if ($struct->hasPackerMethod($pack_name, PackerBase::METHOD_UNPACK)) {
                $build_method |= PackerBase::METHOD_UNPACK;
            }
        } else {
            //如果 这个packer 不生成这种类型 struct 的代码
            if (!$this->build_opt->hasPackerStruct($pack_name, $struct_type)) {
                return;
            }
            $main_packer = $packer->getMainPacker();
            if (!$main_packer) {
                $main_packer = $packer;
            }
            //该packer只生成指定了packer-extra的方法
            if ($main_packer->getExtraFlag() && !$struct->isSetExtraPacker($pack_name)) {
                return;
            }
            if ($this->isBuildPackMethod($packer->getCodeSide())) {
                $build_method |= PackerBase::METHOD_PACK;
                $struct->addPackerMethod($pack_name, PackerBase::METHOD_PACK);
            }
            if ($this->isBuildUnpackMethod($packer->getCodeSide())) {
                $build_method |= PackerBase::METHOD_UNPACK;
                $struct->addPackerMethod($pack_name, PackerBase::METHOD_UNPACK);
            }
        }
        if (($build_method & PackerBase::METHOD_PACK) > 0) {
            $packer->setCurrentMethod(PackerBase::METHOD_PACK);
            $packer->buildPackMethod($struct, $code_buf);
        }
        if (($build_method & PackerBase::METHOD_UNPACK) > 0) {
            $packer->setCurrentMethod(PackerBase::METHOD_UNPACK);
            $packer->buildUnpackMethod($struct, $code_buf);
        }
    }

    /**
     * 获取
     * @param string $pack_type
     * @return PackerBase
     * @throws Exception
     */
    public function getPackInstance($pack_type)
    {
        if (isset($this->pack_instance_arr[$pack_type])) {
            return $this->pack_instance_arr[$pack_type];
        }
        $class_name = ucfirst($pack_type) . 'Pack';
        $base_dir = $this->manager->getCoderPath($this->coder_name);
        $file = $base_dir . $class_name . '.php';
        //文件不存在
        if (!is_file($file)) {
            throw new Exception('Can not find packer ' . $pack_type . ' file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $ns = 'FFan\Dop\Coder\\' . $this->coder_name . '\\';
        $full_class_name = $ns . $class_name;
        if (!class_exists($full_class_name)) {
            throw new Exception('Can not load class ' . $full_class_name);
        }
        $parents = class_parents($full_class_name);
        if (!isset($parents['FFan\Dop\Build\PackerBase'])) {
            throw new Exception('Class ' . $full_class_name . ' must extend of PackerBase');
        }
        /** @var PackerBase $packer */
        $packer = new $full_class_name($this);
        $packer->onLoad();
        $this->pack_instance_arr[$pack_type] = $packer;
        return $packer;
    }

    /**
     * 是否需要生成Encode方法
     * @param int $packer_code_side
     * @return bool
     */
    private function isBuildPackMethod($packer_code_side)
    {
        $result = false;
        if (($packer_code_side & BuildOption::SIDE_CLIENT) > 0) {
            $result = true;
        }
        if (($packer_code_side & BuildOption::SIDE_SERVER) > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * 是否需要生成Decode方法
     * @param int $packer_code_side
     * @return bool
     */
    private function isBuildUnpackMethod($packer_code_side)
    {
        $result = false;
        if (($packer_code_side & BuildOption::SIDE_CLIENT) > 0) {
            $result = true;
        }
        if (($packer_code_side & BuildOption::SIDE_SERVER) > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * 注册自定义数据打包器
     * @param string $name
     * @param string $class_file
     */
    public function registerPacker($name, $class_file)
    {
        if (!isset($this->reg_packer[$name])) {
            $this->manager->buildLogError('Packer ' . $name . ' conflict');
            return;
        }
        $this->reg_packer[$name] = $class_file;
    }

    /**
     * 连接命名空间
     * @param string $ns
     * @param string $class_name
     * @param string $separator
     * @return string
     */
    public function joinNameSpace($ns, $class_name = '', $separator = '/')
    {
        $result = $this->build_opt->namespace_prefix;
        if (!empty($ns)) {
            $len = strlen($result);
            if ($separator !== $result[$len - 1]) {
                $result .= $separator;
            }
            if ($separator === $ns[0]) {
                $ns = substr($ns, 1);
            }
            $result .= $ns;
        }
        if (!empty($class_name)) {
            $len = strlen($result);
            if ($separator !== $result[$len - 1]) {
                $result .= $separator;
            }
            $result .= $class_name;
        }
        return $result;
    }

    /**
     * 在某个文件夹的某个文件里找某个code_buf
     * @param string $path
     * @param string $file
     * @param string $buf_name
     * @return CodeBuf|null
     */
    public function getBuf($path, $file, $buf_name)
    {
        $folder = $this->getFolder();
        $dop_file = $folder->getFile($path, $file);
        if (!$dop_file) {
            return null;
        }
        return $dop_file->getBuf($buf_name);
    }

    /**
     * 加载一个模板，并将内容写入FileBuf
     * @param FileBuf $file_buf
     * @param string $tpl_name
     * @param null $data
     * @throws Exception
     */
    public function loadTpl(FileBuf $file_buf, $tpl_name, $data = null)
    {
        $path = $this->manager->getCoderPath($this->coder_name);
        $tpl_file = FFanUtils::joinFilePath($path, $tpl_name);
        $tpl_loader = TplLoader::getInstance($tpl_file);
        $tpl_loader->execute($file_buf, $data);
    }

    /**
     * 返回两个路径之间的相对引用路径
     * @param string $path 引用的类路径
     * @param string $this_path 当前的路径
     * @return string
     */
    public static function relativePath($path, $this_path)
    {
        static $cache_path = array();
        if ($this_path === $path) {
            $result = './';
        } else {
            //两个目录之间的相对关系增加缓存机制，减少系统开销时间
            $key = $path . ':' . $this_path;
            if (isset($cache_path[$key])) {
                $relative_path = $cache_path[$key];
            } else {
                $require_path_arr = FFanStr::split($path, '/');
                $current_path_arr = FFanStr::split($this_path, '/');
                $len = min(count($current_path_arr), count($require_path_arr));
                for ($i = 0; $i < $len; ++$i) {
                    $tmp_path = current($require_path_arr);
                    $tmp_ns = current($current_path_arr);
                    if ($tmp_ns !== $tmp_path) {
                        break;
                    }
                    array_shift($require_path_arr);
                    array_shift($current_path_arr);
                }
                $relative_path = str_repeat('../', count($current_path_arr));
                if (!empty($require_path_arr)) {
                    $relative_path .= join('/', $require_path_arr) . '/';
                }
                $cache_path[$key] = $relative_path;
            }
            $result = $relative_path;
        }
        if ('.' !== $result[0]) {
            $result = './' . $result;
        }
        return $result;
    }

    /**
     * 获取Manager实例
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * 获取生成选项
     * @return BuildOption
     */
    public function getBuildOption()
    {
        return $this->build_opt;
    }

    /**
     * 读取类文件配置
     * @param FileBuf $file_buf
     * @param Struct $struct
     */
    protected function readClassConfig($file_buf, $struct)
    {
        $extends_buf = new StrBuf();
        $file_buf->setBuf(FileBuf::EXTENDS_BUF, $extends_buf);
        $implements_buf = new StrBuf();
        $file_buf->setBuf(FileBuf::IMPLEMENT_BUF, $implements_buf);
        $struct_type = $struct->getType();
        if (Struct::TYPE_REQUEST === $struct_type) {
            $conf_name = 'request';
        } elseif (Struct::TYPE_RESPONSE === $struct_type) {
            $conf_name = 'response';
        } elseif (Struct::TYPE_DATA === $struct_type) {
            $conf_name = 'data';
        } else {
            return;
        }
        $extends = $this->getConfigString($conf_name . '_class_extends');
        if (!empty($extends)) {
            $extends = FFanStr::split($extends, ',');
            foreach ($extends as $item) {
                $extends_buf->pushStr($item);
            }
        }
        $implement = $this->getConfigString($conf_name . '_class_implements');
        if (!empty($implement)) {
            $implement = FFanStr::split($implement, ',');
            foreach ($implement as $item) {
                $implements_buf->pushStr($item);
            }
        }
        $imports = $this->getConfigString($conf_name . '_class_import');
        if (!empty($imports)) {
            $imports = FFanStr::split($imports, '|');
            $import_buf = $file_buf->getBuf(FileBuf::IMPORT_BUF);
            if ($import_buf) {
                foreach ($imports as $str) {
                    $import_buf->pushStr($str);
                }
            }
        }
    }

    /**
     * class name 生成
     * @param StrBuf $class_name_buf
     * @param FileBuf $file_buf
     */
    protected function fixClassName($class_name_buf, $file_buf)
    {
        /** @var StrBuf $extend_buf */
        $extend_buf = $file_buf->getBuf(FileBuf::EXTENDS_BUF);
        if ($extend_buf && !$extend_buf->isEmpty()) {
            $extend_buf->setJoinStr($this->extends_join_char);
            $class_name_buf->pushStr($this->extends_flag . $extend_buf->dump());
        }
        /** @var StrBuf $implement_buf */
        $implement_buf = $file_buf->getBuf(FileBuf::IMPLEMENT_BUF);
        if ($implement_buf && !$implement_buf->isEmpty()) {
            $implement_buf->setJoinStr($this->implements_join_char);
            $class_name_buf->pushStr($this->implements_flag . $implement_buf->dump());
        }
    }

    /**
     * 获取属性名称
     * @param string $camel_name
     * @param Item $item
     * @return string
     */
    public function fixPropertyName($camel_name, $item)
    {
        $result_name = BuildOption::CAMEL_NAME === $this->build_opt->item_name_property ? $camel_name : $item->getUnderLineName();
        //如果有名字映射, 转换
        if (isset(static::$name_convert_map[$result_name])) {
            return static::$name_convert_map[$result_name];
        }
        return $result_name;
    }

    /**
     * 获取输出的字段名
     * @param string $camel_name
     * @param Item $item
     * @return string
     */
    public function fixOutputName($camel_name, $item)
    {
        if ($item->isKeepOriginalName()) {
            return $item->getOriginalName();
        }
        if (BuildOption::UNDERLINE_NAME === $this->build_opt->item_name_output) {
            return $item->getUnderLineName();
        } else {
            return $camel_name;
        }
    }

    /**
     * 返回coder 名称
     * @return string
     */
    public function getName()
    {
        return $this->coder_name;
    }
}
