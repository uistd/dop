<?php
namespace ffan\dop\build;

/**
 * Interface BufInterface
 * @package ffan\dop\build
 */
interface BufInterface
{
    /**
     * 导出数据
     * @return string
     */
    public function dump();

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty();
}
