<?php
namespace ffan\dop\build;

/**
 * Interface BufInterface
 * @package ffan\dop\build
 */
interface BufInterface
{
    /**
     * BufInterface constructor.
     * @param string $name
     */
    public function __construct($name = null);

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

    /**
     * 转换成字符串
     * @return string
     */
    public function __toString();

    /**
     * 写入一段字符串 或者 Buf
     * @param string|BufInterface $item
     * @return $this
     */
    public function push($item);
    
    /**
     * 获取name
     * @return string|null
     */
    public function getName();
}
