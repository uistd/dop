<?php

namespace ffan\dop\plugin\mock;

use ffan\dop\BuildOption;
use ffan\dop\CodeBuf;
use ffan\dop\Item;
use ffan\dop\pack\php\Generator;
use ffan\dop\plugin\PluginCode;
use ffan\dop\Struct;

/**
 * Class PhpMockCode
 * @package ffan\dop\plugin\mock
 */
class PhpMockCode implements PluginCode
{
    /**
     * 因为Mock是一个单独的文件，所以不用传入的code_buf
     * @var CodeBuf
     */
    private static $code_buf;

    /**
     * PHP 相关插件代码
     * @param BuildOption $build_opt
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    public static function pluginCode(BuildOption $build_opt, CodeBuf $code_buf, Struct $struct)
    {
        $mock_buf = self::$code_buf;
        if (null === $mock_buf) {
            $mock_buf = self::$code_buf = new CodeBuf();
            $mock_buf->push('<?php');
            $mock_buf->emptyLine();
            $ns = Generator::phpNameSpace($build_opt, '');
            $mock_buf->push('namespace ' . $ns . ';');
            $mock_buf->emptyLine();
            $mock_buf->push('class DopMock');
            $mock_buf->push('{');
            $mock_buf->indentIncrease();
        }
        $mock_buf->emptyLine();
        $class_name = $struct->getClassName();
        $mock_buf->push('/**');
        $mock_buf->push(' * 生成 ' . $class_name . ' mock数据');
        $mock_buf->push(' */');
        $mock_buf->push('public function mock' . $class_name . '()');
        $mock_buf->push('{');
        $mock_buf->indentIncrease();
        $mock_buf->push('use ' . Generator::phpNameSpace($build_opt, $class_name) . ';');
        $mock_buf->push('$data = new ' . $class_name . '();');
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {
            /** @var MockRule $mock_rule */
            $mock_rule = $item->getPluginData('mock');
            if (null === $mock_rule) {
                continue;
            }
            self::mockItem($mock_buf, '$this->' . $name, $mock_rule, $item);
        }
        $mock_buf->indentDecrease()->push('}');
    }

    /**
     * 生成mock单项的代码
     * @param CodeBuf $mock_buf
     * @param string $mock_item
     * @param MockRule $mock_rule
     * @param Item $item
     * @param int $depth
     */
    private static function mockItem($mock_buf, $mock_item, $mock_rule, $item, $depth = 0)
    {
        static $tmp_arr_index = 0;
        switch ($mock_rule->type) {
            //固定值
            case MockRule::MOCK_FIXED:
                $mock_buf->push($mock_item . ' = ' . $mock_rule->fixed_value . ';');
                break;
            case MockRule::MOCK_ENUM:
                $arr_name = '$tmp_arr_' . $tmp_arr_index;
                $tmp_arr_index++;
                $mock_buf->lineTmp($arr_name . ' = array(');
                $mock_buf->lineTmp(join(',', $mock_rule->enum_set));
                $mock_buf->lineTmp(');');
                $mock_buf->lineFin();
                $mock_buf->push($mock_item . ' = ' . $arr_name . '[array_rand(' . $arr_name . ')];');
                break;
        }
    }

    /**
     * 格式化值
     * @param string $value
     * @param int $type
     */
    private static function formatValue($value, $type)
    {

    }
}
