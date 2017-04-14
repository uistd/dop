<?php

namespace ffan\dop\php;

use ffan\dop\CodeBuf;
use ffan\dop\Item;
use ffan\dop\PackInterface;
use ffan\dop\Struct;

/**
 * Class ArrayPack 数组打包解包
 * @package ffan\dop\php
 */
class ArrayPack implements PackInterface
{

    /**
     * 数据序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildPackMethod($struct, $code_buf)
    {

    }

    /**
     * 数据反序列化
     * @param Struct $struct 结构体
     * @param CodeBuf $code_buf 生成的代码缓存
     * @return void
     */
    public static function buildUnPackMethod($struct, $code_buf)
    {
        $code_buf->emptyLine();
        $code_buf->push('/**');
        $code_buf->push(' * 对象初始化');
        $code_buf->push(' * @param array $data');
        $code_buf->push(' */');
        $code_buf->push('public function arrayUnpack($data)');
        $code_buf->push('{');
        $code_buf->indentIncrease();
        $all_item = $struct->getAllExtendItem();
        /**
         * @var string $name
         * @var Item $item
         */
        foreach ($all_item as $name => $item) {

        }
        $code_buf->indentDecrease();
        $code_buf->push('}');
    }

    /**
     * 解出数据
     * @param string $var_name 值变量名
     * @param string $key_name 键名
     * @param string $data_name 数据变量名
     */
    private static function unpackItemValue($var_name, $key_name, $data_name)
    {
        
    }
}