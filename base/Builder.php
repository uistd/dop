<?php

namespace ffan\dop;

use ffan\dop\build\BuildOption;
use ffan\dop\build\CoderBase;
use ffan\dop\build\FileBuf;
use ffan\dop\build\PluginHandlerBase;
use ffan\dop\protocol\Struct;
use ffan\php\utils\Utils as FFanUtils;

/**
 * Class Builder 文件生成类
 * @package ffan\dop
 */
class Builder
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var BuildOption 生成参数
     */
    protected $build_opt;

    /**
     * @var string 生成代码基础路径
     */
    protected $build_base_path;

    /**
     * @var array 插件的代码生成器
     */
    private $plugin_handler_arr;

    /**
     * @var array 主代码生成器
     */
    private $coder_arr;
    
    /**
     * @var array 文件列表
     */
    private $file_list = [];

    /**
     * @var array 目录检查缓存
     */
    private $patch_check_cache;

    /**
     * Generator constructor.
     * @param Manager $protocol_manager
     * @param BuildOption $build_opt
     */
    public function __construct(Manager $protocol_manager, BuildOption $build_opt)
    {
        $this->manager = $protocol_manager;
        $this->build_opt = $build_opt;
        $this->build_base_path = FFanUtils::fixWithRuntimePath($this->build_opt->build_path);
    }

    /**
     * 生成临时变量
     * @param string $var
     * @param string $type
     * @return string
     */
    public static function tmpVarName($var, $type)
    {
        return $type . '_' . (string)$var;
    }

    /**
     * 生成文件
     */
    public function build()
    {
        $coder = $this->getCoder();
        if (null === $coder) {
            return;
        }
        Exception::setAppendMsg('Build common file');
        $this->buildCommonFile($coder);
        $this->buildStructFile($coder);
        $this->buildNsFile($coder);
        $this->pluginBuild();
        $this->save();
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
     * 生成通用的文件
     * @param CoderBase $coder
     */
    private function buildCommonFile($coder)
    {
        $append_msg = 'Build common file';
        Exception::setAppendMsg($append_msg);
        $coder->buildCommonCode();
    }

    /**
     * 生成struct文件
     * @param CoderBase $coder
     */
    private function buildStructFile($coder)
    {
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            //如果struct是从缓存加载的，这次就不用再生成代码了
            if ($struct->loadFromCache()) {
                continue;
            }
            $append_msg = 'Build struct ' . $struct->getFile() . ' ' . $struct->getClassName();
            Exception::setAppendMsg($append_msg);
            $coder->buildStructCode($struct);
        }
    }

    /**
     * 按命名空间生成文件
     * @param CoderBase $coder
     */
    private function buildNsFile($coder)
    {
        $file_list = $this->manager->getBuildFileList();
        foreach ($file_list as $file) {
            $append_msg = 'Build name space ' . $file;
            Exception::setAppendMsg($append_msg);
            $ns = basename($file, '.xml');
            $coder->buildNsCode($ns);
        }
    }

    /**
     * 获取代码生成对象
     * @return CoderBase
     * @throws Exception
     */
    private function getCoder()
    {
        $code_type = $this->build_opt->getCoderName();
        if (isset($this->coder_arr[$code_type])) {
            return $this->coder_arr[$code_type];
        }
        $class_name = 'Coder';
        $file = dirname(__DIR__) . '/pack/' . $code_type . '/' . $class_name . '.php';
        if (!is_file($file)) {
            throw new Exception('Can not find coder file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = '\ffan\dop\pack\\' . $code_type . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new Exception('Unknown class name ' . $full_class);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['ffan\dop\build\CoderBase'])) {
            throw new Exception('Class ' . $full_class . ' must be implements of CoderBase');
        }
        $this->coder_arr[$code_type] = new $full_class($this, $code_type);
        return $this->coder_arr[$code_type];
    }

    /**
     * 获取插件代码生成器
     * @return array
     */
    private function getPluginHandlers()
    {
        if (NULL !== $this->plugin_handler_arr) {
            return $this->plugin_handler_arr;
        }
        $result = array();
        //$code_type = $this->build_opt->getCoderName();
        //$plugin_list = $this->manager->getPluginList();
        $this->plugin_handler_arr = $result;
        return $result;
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
            throw new Exception('Build result file name '. $file_name .' conflict.');
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

    /**
     * 获取代码生成参数
     * @return BuildOption
     */
    public function getBuildOption()
    {
        return $this->build_opt;
    }

    /**
     * 获取代码生成的基础路径
     * @return string
     */
    public function getBuildBasePath()
    {
        return $this->build_base_path;
    }

    /**
     * 获取协议管理器
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
