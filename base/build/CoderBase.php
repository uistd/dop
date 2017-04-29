<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\dop\protocol\Struct;


/**
 * Class CoderBase 生成器基类
 * @package ffan\dop\build
 */
abstract class CoderBase
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
     * CoderBase constructor.
     * @param Manager $manager
     * @param BuildOption $build_opt
     */
    public function __construct(Manager $manager, BuildOption $build_opt)
    {
        $this->manager = $manager;
        $this->build_opt = $build_opt;
        $this->coder_name = $build_opt->getCoderName();
        $this->build_base_path = $build_opt->build_path;
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
        return $this->manager->getFolder($this->build_opt->build_path);
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
     */
    public function structIterator(callable $callback)
    {
        $all_struct = $this->manager->getAllStruct();
        /** @var Struct $struct */
        foreach ( $all_struct as $struct) {
            if ($struct->loadFromCache()) {
                continue;
            }
            call_user_func($callback, $struct);
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
        $packer_list = $this->build_opt->getPacker();
        $packer_object_arr = array();
        $this->getAllPackerObject($packer_list, $packer_object_arr);
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
     * @param array $packer_object_arr
     */
    private function getAllPackerObject($packer_arr, &$packer_object_arr)
    {
        foreach ($packer_arr as $packer_name) {
            if (isset($packer_object_arr[$packer_name])) {
                continue;
            }
            $packer_object = $this->getPackInstance($packer_name);
            if (null === $packer_object) {
                $this->manager->buildLogError('Can not found packer of ' . $packer_name);
                continue;
            }
            $packer_object_arr[$packer_name] = $packer_object;
            //依赖的其它packer
            $require_packer = $packer_object->getRequirePacker();
            if (!empty($require_packer)) {
                $this->getAllPackerObject($require_packer, $packer_object_arr);
            }
        }
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
        $struct_type = $struct->getType();
        if ($this->isBuildPackMethod($struct_type)) {
            $packer->buildPackMethod($struct, $code_buf);
        }
        if ($this->isBuildUnpackMethod($struct_type)) {
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
        $ns = 'ffan\dop\coder\\' . $this->coder_name . '\\';
        $full_class_name = $ns . $class_name;
        if (!class_exists($full_class_name)) {
            throw new Exception('Can not load class ' . $full_class_name);
        }
        $parents = class_parents($full_class_name);
        if (!isset($parents['ffan\dop\build\PackerBase'])) {
            throw new Exception('Class ' . $full_class_name . ' must extend of PackerBase');
        }
        $this->pack_instance_arr[$pack_type] = new $full_class_name();
        return $this->pack_instance_arr[$pack_type];
    }

    /**
     * 是否需要生成Encode方法
     * @param int $type
     * @return bool
     */
    private function isBuildPackMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,服务端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            //如果是Request 客户端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return $result;
    }


    /**
     * 是否需要生成Decode方法
     * @param int $type
     * @return bool
     */
    private function isBuildUnpackMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,客户端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            //如果是Request 服务端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
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
     * @param string $separator
     * @return string
     */
    public function joinNameSpace($ns, $separator = '/')
    {
        $result = $this->build_opt->namespace_prefix;
        $len = strlen($result);
        if ($separator !== $result[$len - 1]) {
            $result .= $separator;
        }
        if (!empty($ns)) {
            if ($separator === $ns[0]) {
                $ns = substr($ns, 1);
            }
            $result .= $ns;
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
}
