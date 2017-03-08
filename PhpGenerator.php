<?php
namespace ffan\dop;

use ffan\php\tpl\Tpl;

/**
 * Class PhpGenerator
 * @package ffan\dop
 */
class PhpGenerator extends DOPGenerator
{
    /**
     * @var string 模板文件
     */
    protected $tpl = 'php/php.tpl';

    /**
     * PhpGenerator constructor.
     * @param ProtocolManager $protocol_manager
     */
    public function __construct(ProtocolManager $protocol_manager)
    {
        parent::__construct($protocol_manager);
        //注册一些私有的修正器
        Tpl::registerGrep('php_ns', array('ffan\dop\PhpGenerator', 'phpNameSpace'));
        Tpl::registerGrep('php_var_type', array('ffan\dop\PhpGenerator', 'varType'));
        Tpl::registerPlugin('php_item_int', array('ffan\dop\PhpGenerator', 'phpItemInit'));
    }

    /**
     * 变更初始化
     * @param array $args
     * @return string
     */
    public static function phpItemInit($args)
    {
        return Tpl::get('php/item_init.tpl', $args);
    }
    
    /**
     * PHP命名空间的修正器
     * @param string $ns
     * @return mixed|string
     */
    public static function phpNameSpace($ns)
    {
        $ns = str_replace('/', '\\', $ns);
        return $ns;
    }

    /**
     * 变量类型
     * @param Item $item
     * @return string
     */
    public static function varType(Item $item)
    {
        $type = $item->getType();
        $str = 'mixed';
        switch($type) {
            case ItemType::BINARY:
            case ItemType::STRING:
                $str = 'string';
                break;
            case ItemType::FLOAT:
                $str = 'float';
                break;
            case ItemType::STRUCT;
                /** @var StructItem $item */
                $str = $item->getStructName();
                break;
            case ItemType::MAP;
                $str = 'array';
                break;
            case ItemType::ARR:
                /** @var ListItem $item */
                $sub_item = $item->getItem();
                $sub_type = self::varType($sub_item);
                $str = 'array['. $sub_type. ']';
                break;
            case ItemType::INT:
                $str = 'int';
                break;
        }
        return $str;
    }

    /**
     * 整理生成文件的参数
     * @return array
     */
    protected function buildTplData()
    {
        $build_arg = array(
            'main_namespace' => $this->protocol_manager->getMainNameSpace(),
            'path_define_var' => $this->protocol_manager->getConfig('path_define_var', 'DOP_PATH'),
            'build_path' => $this->protocol_manager->getBuildPath(),
            'code_namespace' => 'namespace',
            'code_php_tag' => '<?php'
        );
        return $build_arg;
    }
}