<?php

namespace ffan\dop\build;
use ffan\dop\Exception;

/**
 * Class StrBuf
 * @package ffan\dop\build
 */
class StrBuf implements BufInterface
{
    /**
     * 代码段级别
     */
    const BUF_LEVEL = 1;
    
    /**
     * @var string 连接字符
     */
    private $join_str;

    /**
     * @var array
     */
    private $str_buffer = [];

    /**
     * @var bool 是否有子buf
     */
    private $has_sub_buf = false;

    /**
     * StrBuf constructor.
     * @param string $join_str 连接字符串
     */
    public function __construct($join_str = '')
    {
        $this->join_str = $join_str;
    }

    /**
     * 写入一个字符串
     * @param string $str
     */
    public function push($str)
    {
        $this->str_buffer[] = $str;
    }

    /**
     * 导出字符串
     * @return string
     */
    public function dump()
    {
        if ($this->has_sub_buf) {
            /**
             * @var int $i
             * @var BufInterface $each_buf
             */
            foreach ($this->str_buffer as $i => $each_buf) {
                if (is_string($each_buf) || $each_buf->isEmpty()) {
                    continue;
                }
                /** @var StrBuf $each_buf */
                $this->str_buffer[$i] = $each_buf->dump();
            }
        }
        $result = join($this->join_str, $this->str_buffer);
        $this->str_buffer = null;
        return $result;
    }

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->str_buffer);
    }

    /**
     * 插入子buf
     * @param BufInterface $sub_buf
     * @return $this
     * @throws Exception
     */
    public function insertBuf(BufInterface $sub_buf)
    {
        if ($sub_buf === $this) {
            throw new Exception('Can not insert self');
        }
        $this->str_buffer[] = $sub_buf;
        $this->has_sub_buf = true;
        return $this;
    }

    /**
     * 设置缩进
     * @param int $indent
     * @return void
     */
    public function setIndent($indent)
    {
        // TODO: Implement setIndent() method.
    }
}