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

    /**
     * 插入子buf
     * @param BufInterface $sub_buf
     * @return $this
     */
    public function insertBuf(BufInterface $sub_buf);

    /**
     * 设置缩进
     * @param int $indent
     * @return void
     */
    public function setIndent($indent);
}
