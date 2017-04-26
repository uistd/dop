<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\dop\protocol\Struct;
use ffan\php\utils\Utils as FFanUtils;

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
     * @var array 文件列表
     */
    private $file_list = [];

    /**
     * @var array 目录检查缓存
     */
    private $patch_check_cache;

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
        $this->buildStructFile();
        $this->buildNsFile();
        $this->pluginBuild();
        $this->save();
    }

    /**
     * 生成struct文件
     */
    private function buildStructFile()
    {
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            //如果struct是从缓存加载的，这次就不用再生成代码了
            if ($struct->loadFromCache()) {
                continue;
            }
            $append_msg = 'Build struct ' . $struct->getFile() . ' ' . $struct->getClassName();
            Exception::setAppendMsg($append_msg);
            $this->buildStructCode($struct);
        }
    }

    /**
     * 按命名空间生成文件
     */
    private function buildNsFile()
    {
        $file_list = $this->manager->getBuildFileList();
        foreach ($file_list as $file) {
            $append_msg = 'Build name space ' . $file;
            Exception::setAppendMsg($append_msg);
            $ns = basename($file, '.xml');
            $this->buildNsCode($ns);
        }
    }

    /**
     * 生成插件内容
     */
    private function pluginBuild()
    {
        $plugin_coder_arr = $this->getPluginHandlers();
        /**
         * @var string $name
         * @var PluginHandlerBase $plugin_coder
         */
        foreach ($plugin_coder_arr as $name => $plugin_coder) {
            $plugin_append_msg = 'Build plugin code: ' . $plugin_coder->getName();
            Exception::setAppendMsg($plugin_append_msg);
            $plugin_coder->buildCode($this);
        }
    }

    /**
     * 获取插件代码生成器
     * @return array
     */
    private function getPluginHandlers()
    {
        $result = array();
        //$code_type = $this->build_opt->getCoderName();
        //$plugin_list = $this->manager->getPluginList();
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
    public function buildStructCode($struct)
    {
    }

    /**
     * 按协议命名空间生成代码
     * @param string $name_space
     * @return void
     */
    public function buildNsCode($name_space)
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
     * 添加文件
     * @param FileBuf $file
     * @throws Exception
     */
    public function addFile(FileBuf $file)
    {
        $file_name = $file->getFileName();
        $tmp_name = self::cleanName($file_name);
        if (isset($this->file_list[$tmp_name])) {
            throw new Exception('Build result file name ' . $file_name . ' conflict.');
        }
        $this->file_list[$tmp_name] = $file;
    }

    /**
     * 获取文件
     * @param string $file_name
     * @return FileBuf|null
     */
    public function getFile($file_name)
    {
        $tmp_name = self::cleanName($file_name);
        if (!isset($this->file_list[$tmp_name])) {
            return null;
        }
        return $this->file_list[$tmp_name];
    }

    /**
     * 处理一个文件名，当作数组的唯一key
     * @param string $file_name
     * @return string
     */
    private static function cleanName($file_name)
    {
        //转换成小写，去掉首尾的 空格 和 / 号
        return trim(strtolower($file_name), ' /');
    }

    /**
     * 保存每个文件
     */
    public function save()
    {
        /**
         * @var FileBuf $file_buf
         */
        foreach ($this->file_list as $file_buf) {
            $this->writeFile($file_buf);
        }
    }

    /**
     * 写文件
     * @param FileBuf $file_buf
     * @throws Exception
     */
    private function writeFile($file_buf)
    {
        if ($file_buf->isEmpty()) {
            return;
        }
        $file_name = $file_buf->getFileName();
        $file_path = dirname($file_name);
        $this->checkPatch($file_path);
        $full_file_name = FFanUtils::joinFilePath($this->build_base_path, $file_name);
        $content = $file_buf->dump();
        $re = file_put_contents($full_file_name, $content);
        if (false === $re) {
            throw new Exception('Can not write file ' . $full_file_name);
        }
        $this->manager->buildLog('Build file ' . $file_name . ' success');
    }

    /**
     * 目录检查
     * @param string $path
     */
    private function checkPatch($path)
    {
        if (isset($this->patch_check_cache[$path])) {
            return;
        }
        $file_path = FFanUtils::joinPath($this->build_base_path, $path);
        FFanUtils::pathWriteCheck($file_path);
        $this->patch_check_cache[$path] = true;
    }
}
