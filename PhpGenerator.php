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
        //命名空间
        Tpl::registerGrep('php_ns', array('ffan\dop\PhpGenerator', 'phpNameSpace'));
        //类型
        Tpl::registerGrep('php_var_type', array('ffan\dop\PhpGenerator', 'varType'));
        //变量值初始化
        Tpl::registerPlugin('php_item_init', array('ffan\dop\PhpGenerator', 'phpItemInit'));
        //检查是不是非常 简单的类型
        Tpl::registerGrep('php_simple_type', array('ffan\dop\PhpGenerator', 'isSimpleType'));
        //是否需要检查是否需要判断数组
        Tpl::registerGrep('php_array_check', function($type){
            return ItemType::ARR === $type || ItemType::MAP === $type || ItemType::STRUCT === $type;
        });
    }

    /**
     * 是否是简单的类型
     * 简单类型就可以直接赋值
     * @param int $type
     * @param Item $item
     * @return bool
     */
    public static function isSimpleType($type, $item)
    {
        if (ItemType::BINARY === $type || ItemType::FLOAT === $type || ItemType::STRING === $type || ItemType::INT === $type) {
            return true;
        }
        if (ItemType::ARR === $type) {
            /** @var ListItem $item */
            $sub_item = $item->getItem();
            return self::isSimpleType($sub_item->getType(), $sub_item);
        }
        return false;
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
        switch ($type) {
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
                $str = 'array[' . $sub_type . ']';
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
            'code_php_tag' => "<?php\n"
        );
        return $build_arg;
    }
}