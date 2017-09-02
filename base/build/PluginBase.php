<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Manager;
use ffan\dop\protocol\Item;
use ffan\dop\protocol\Protocol;
use FFan\Std\Common\ConfigBase;
use FFan\Std\Common\Utils as FFanUtils;

/**
 * Class PluginBase 插件基类
 * @package ffan\dop\build
 */
abstract class PluginBase extends ConfigBase
{
    /**
     * @var Manager;
     */
    protected $manager;

    /**
     * @var string 插件名
     */
    protected $plugin_name;

    /**
     * @var bool 是否存在插件的模板
     */
    protected $has_tpl;

    /**
     * @var string 模板文件
     */
    protected $tpl_name;

    /**
     * @var array 插件处理
     */
    private $handler_list = array();

    /**
     * @var string 所在路径
     */
    private $base_path;

    /**
     * @var CoderBase
     */
    private $coder;

    /**
     * PluginInterface constructor.
     * @param Manager $manager
     * @param string $name
     */
    public function __construct(Manager $manager, $name)
    {
        $this->manager = $manager;
        $this->plugin_name = $name;
        $this->base_path = $manager->getPluginMainPath($this->plugin_name);
        $conf_arr = $manager->getPluginConfig($name);
        if (!empty($conf_arr)) {
            $this->initConfig($conf_arr);
        }
    }

    /**
     * 初始化
     * @param Protocol $parser 解析器
     * @param \DOMElement $node
     * @param Item $item
     * @return
     */
    abstract public function init(Protocol $parser, \DOMElement $node, Item $item);

    /**
     * 获取插件名称
     * @return string
     * @throws Exception
     */
    public function getPluginName()
    {
        if (null === $this->plugin_name) {
            throw new Exception('Property name required!');
        }
        return $this->plugin_name;
    }

    /**
     * 注册一个插件处理器
     * @param string $coder_name 代码生成器名称
     * @param string $class_file
     * @throws Exception
     */
    public function registerHandler($coder_name, $class_file)
    {
        if (isset($this->handler_list[$coder_name])) {
            throw new Exception('Plugin ' . $this->plugin_name . ' coder:' . $coder_name . ' exist!');
        }
        $this->handler_list[$coder_name] = $class_file;
    }

    /**
     * 获取某种语言的代码生成实例
     * @param string $coder_name
     * @return PluginCoderBase
     */
    public function getPluginCoder($coder_name)
    {
        $class_name = $this->codeClassName($coder_name);
        //如果有注册插件 处理器
        if (isset($this->handler_list[$coder_name])) {
            $file = $this->handler_list[$coder_name];
        } else {
            $base_dir = $this->manager->getPluginMainPath($this->plugin_name);
            $file = $base_dir . 'coder/' . $class_name . '.php';
        }
        $coder = null;
        if (is_file($file)) {
            $full_class = $this->codeClassName($coder_name, true);
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            //类是否存在
            if (class_exists($full_class)) {
                $parents = class_parents($full_class);
                //类是否 继续 PluginCoderBase
                if (isset($parents['ffan\dop\build\PluginCoderBase'])) {
                    $coder = new $full_class($this);
                }
            }
        }
        return $coder;
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
        $path = $this->manager->getPluginMainPath($this->plugin_name);
        $tpl_file = FFanUtils::joinFilePath($path, $tpl_name);
        $tpl_loader = TplLoader::getInstance($tpl_file);
        $tpl_loader->execute($file_buf, $data);
    }

    /**
     * 生成代码的类名
     * @param string $coder_name 生成器名称
     * @param bool $ns 是否带全名空间
     * @return string
     */
    private function codeClassName($coder_name, $ns = false)
    {
        $class_name = ucfirst($coder_name) . ucfirst($this->plugin_name) . 'Coder';
        if ($ns) {
            $ns_str = 'ffan\dop\plugin\\' . $this->plugin_name;
            $class_name = $ns_str . '\\' . $class_name;
        }
        return $class_name;
    }

    /**
     * 获取protocolManager对象
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * 获取当前的coder
     * @return CoderBase
     */
    public function getCoder()
    {
        if (null === $this->coder) {
            $this->coder = $this->manager->getCurrentCoder();
        }
        return $this->coder;
    }

    /**
     * 获取代码生成目录
     * @return string
     */
    public function getBuildPath()
    {
        return $this->getConfigString('build_path', 'plugin_'. $this->plugin_name);
    }

    /**
     * 获取命名空间
     */
    public function getNameSpace()
    {
        $ns = $this->getConfigString('namespace');
        if (!empty($ns)) {
            return $ns;
        } else {
            return $this->getCoder()->joinNameSpace('plugin/'. $this->plugin_name);
        }
    }
}
