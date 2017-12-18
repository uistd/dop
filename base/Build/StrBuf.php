<?php

namespace UiStd\Dop\Build;
use UiStd\Dop\Exception;

/**
 * Class StrBuf
 * @package UiStd\Dop\Build
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
    private $join_str = '';

    /**
     * @var array
     */
    private $str_buffer = [];

    /**
     * @var bool 是否有子buf
     */
    private $has_sub_buf = false;

    /**
     * @var int 缩进
     */
    private $indent = 0;

    /**
     * @var string 名称
     */
    private $name;
    
    /**
     * StrBuf constructor.
     * @param string $name buf name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * 设置连接字符串
     * @param string $join_str 连接字符串
     * @return $this
     */
    public function setJoinStr($join_str)
    {
        $this->join_str = $join_str;
        return $this;
    }

    /**
     * 写入一个字符串
     * @param string $str
     */
    public function pushStr($str)
    {
        $this->str_buffer[] = $str;
    }

    /**
     * 移除最后一项
     * @return string|BufInterface
     */
    public function pop()
    {
        return array_pop($this->str_buffer);
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
        if ($this->indent > 0) {
            $result = CodeBuf::indentSpace($this->indent) . $result;
        }
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
        $this->indent = $indent;
    }

    /**
     * 转换成字符串
     * @return string
     */
    public function __toString()
    {
        return $this->dump();
    }
    
    /**
     * 写入一段字符串 或者 Buf
     * @param string|BufInterface $item
     * @return $this
     */
    public function push($item)
    {
        if (is_string($item)) {
            $this->pushStr($item);
        } elseif (is_object($item) && $item instanceof BufInterface) {
            $this->insertBuf($item);
        } else {
            $this->pushStr((string)$item);
        }
        return $this;
    }

    /**
     * 获取name
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
}
