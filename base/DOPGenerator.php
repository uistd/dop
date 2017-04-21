<?php

namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;

/**
 * Class DOPGenerator 生成文件
 * @package ffan\dop
 */
class DOPGenerator
{
    /**
     * @var ProtocolManager
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
    private $plugin_coder_arr;

    /**
     * @var array 主代码生成器
     */
    private $coder_arr;

    /**
     * Generator constructor.
     * @param ProtocolManager $protocol_manager
     * @param BuildOption $build_opt
     */
    public function __construct(ProtocolManager $protocol_manager, BuildOption $build_opt)
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
    public function generate()
    {
        $coder = $this->getCoder();
        if (null === $coder) {
            return;
        }
        DOPException::setAppendMsg('Build common file');
        $this->buildCommonFile($coder);
        $this->buildStructFile($coder);
        $this->buildNsFile($coder);
    }

    /**
     * 生成通用的文件
     * @param CoderBase $coder
     */
    private function buildCommonFile($coder)
    {
        $append_msg = 'Build common file';
        //DOPException::setAppendMsg($append_msg);
        $file_buf = new FileBuf();
        $coder->buildCommonCode($file_buf);
        $plugin_coder_arr = $this->getPluginCoder();
        /**
         * @var string $name
         * @var PluginCoder $plugin_coder
         */
        foreach ($plugin_coder_arr as $name => $plugin_coder) {
            $plugin_append_msg = $append_msg . ' plugin '. $plugin_coder->getName();
            DOPException::setAppendMsg($plugin_append_msg);
            $plugin_coder->buildCommonCode($file_buf);
            break;
        }
        $this->saveFile($file_buf);
    }

    /**
     * 生成struct文件
     * @param CoderBase $coder
     */
    private function buildStructFile($coder)
    {
        $use_cache = $this->build_opt->allow_cache;
        $plugin_generator = $this->getPluginCoder();
        /** @var Struct $struct */
        foreach ($this->manager->getAllStruct() as $struct) {
            if ($use_cache && $struct->isCached()) {
                continue;
            }
            $append_msg = 'Build struct '. $struct->getFile() .' '. $struct->getClassName();
            DOPException::setAppendMsg($append_msg);
            $file_buf = new FileBuf();
            //暂时设置一个文件路径，可以修改
            $file_buf->setRelatePath($struct->getNamespace());
            $coder->buildStructCode($struct, $file_buf);
            /**
             * @var string $name
             * @var PluginCoder $plugin_coder
             */
            foreach ($plugin_generator as $name => $plugin_coder) {
                $plugin_append_msg = $append_msg .' plugin '. $plugin_coder->getName();
                DOPException::setAppendMsg($plugin_append_msg);
                $plugin_coder->buildStructCode($struct, $file_buf);
            }
            $this->saveFile($file_buf);
        }
    }

    /**
     * 按命名空间生成文件
     * @param CoderBase $coder
     */
    private function buildNsFile($coder)
    {
        $use_cache = $this->build_opt->allow_cache;
        $file_list = $use_cache ? $this->manager->getBuildFileList() : $this->manager->getAllFileList();
        $plugin_generator = $this->getPluginCoder();
        foreach ($file_list as $file) {
            $append_msg = 'Build name space '. $file;
            DOPException::setAppendMsg($append_msg);
            $file_buf = new FileBuf();
            $ns = basename($file, '.xml');
            $coder->buildNsCode($ns, $file_buf);
            /**
             * @var string $name
             * @var PluginCoder $plugin_coder
             */
            foreach ($plugin_generator as $name => $plugin_coder) {
                $plugin_append_msg = $append_msg . ' plugin '. $plugin_coder->getName();
                DOPException::setAppendMsg($plugin_append_msg);
                $plugin_coder->buildNsCode($ns, $file_buf);
            }
            $this->saveFile($file_buf);
        }
    }
    
    /**
     * 获取代码生成对象
     * @return CoderBase
     * @throws DOPException
     */
    private function getCoder()
    {
        $code_type = $this->build_opt->getCodeType();
        if (isset($this->coder_arr[$code_type])) {
            return $this->coder_arr[$code_type];
        }
        $class_name = 'Coder';
        $file = dirname(__DIR__) . '/pack/' . $code_type . '/' . $class_name . '.php';
        if (!is_file($file)) {
            throw new DOPException('Can not find coder file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $full_class = '\ffan\dop\pack\\' . $code_type . '\\' . $class_name;
        if (!class_exists($full_class)) {
            throw new DOPException('Unknown class name ' . $full_class);
        }
        $parents = class_parents($full_class);
        if (!isset($parents['ffan\dop\CoderBase'])) {
            throw new DOPException('Class ' . $full_class . ' must be implements of CoderBase');
        }
        $this->coder_arr[$code_type] = new $full_class($this, $code_type);
        return $this->coder_arr[$code_type];
    }
    
    /**
     * 获取插件代码生成器
     * @return array
     */
    private function getPluginCoder()
    {
        if (NULL !== $this->plugin_coder_arr) {
            return $this->plugin_coder_arr;
        }
        $result = array();
        $code_type = $this->build_opt->getCodeType();
        $plugin_list = $this->manager->getPluginList();
        if (null !== $plugin_list) {
            /**
             * @var string $name
             * @var Plugin $plugin
             */
            foreach ($plugin_list as $name => $plugin) {
                if (!$this->build_opt->usePlugin($name)) {
                    continue;
                }
                $coder = $plugin->getPluginCoder($code_type);
                if (null !== $coder) {
                    $result[$name] = $coder;
                }
            }
        }
        $this->plugin_coder_arr = $result;
        return $result;
    }
    
    /**
     * 保存文件
     * @param FileBuf $file_buf
     * @throws DOPException
     */
    public function saveFile(FileBuf $file_buf)
    {
        static $path_check = array();
        if ($file_buf->main_buf->isEmpty()) {
            return;
        }
        $content = $file_buf->getContent();
        if (empty($content)) {
            return;
        }
        $file_name = $file_buf->getFileName();
        if (empty($file_name)) {
            $this->manager->buildLogError('Can not save file, no file_name');
        }
        $file_path = $this->build_base_path;
        $relate_path = $file_buf->getRelatePath();
        if (!empty($relate_path)) {
            $file_path = FFanUtils::joinPath($this->build_base_path, $relate_path);
        }
        if (!isset($path_check[$file_path])) {
            FFanUtils::pathWriteCheck($file_path);
            $path_check[$file_path] = true;
        }
        $full_file_name = FFanUtils::joinFilePath($file_path, $file_name);
        $re = file_put_contents($full_file_name, $content);
        if (false === $re) {
            throw new DOPException('Can not write file ' . $full_file_name);
        }
        $this->manager->buildLog('Build file ' . $file_name . ' success');
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
     * @return ProtocolManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
