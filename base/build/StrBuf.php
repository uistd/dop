<?php

namespace ffan\dop\build;

/**
 * Class StrBuf
 * @package ffan\dop\build
 */
class StrBuf
{
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
     * 写入一个str buf
     * @param StrBuf $buf
     */
    public function pushBuf(StrBuf $buf)
    {
        $this->str_buffer[] = $buf;
        $this->has_sub_buf = true;
    }

    /**
     * 导出字符串
     * @return string
     */
    public function dump()
    {
        if ($this->has_sub_buf) {
            foreach ($this->str_buffer as $i => $each_buf) {
                if (is_string($each_buf)) {
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
}