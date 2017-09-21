<?php

namespace FFan\Dop\Build;

use FFan\Dop\Exception;

/**
 * Class CodeBuf
 * @package FFan\Dop\Build
 */
class CodeBuf implements BufInterface
{
    /**
     * 子buf类型
     */
    const SUB_BUF_TYPE_CODE = 1;
    const SUB_BUF_TYPE_STR = 2;

    /**
     * @var int 缩进级别
     */
    private $indent = 0;

    /**
     * @var array 内容缓存
     */
    private $line_buffer = [];

    /**
     * @var array 代码锁，用于控制一行代码只允许出现一次的场景
     */
    private $code_lock;

    /**
     * @var string 缩进字符串， 默认是4个空格
     */
    private static $indent_space = '    ';

    /**
     * @var array 子buffer 数据
     */
    private $sub_buffer_list;

    /**
     * @var int 全局缩进
     */
    private $global_indent;

    /**
     * @var string 名称
     */
    private $name;

    /**
     * @var bool 是否在内容前增加空行
     */
    private $prefix_empty_line;

    /**
     * CodeBuf constructor.
     * @param string $name buf name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * 设置全局缩进
     * @param int $global_indent
     */
    public function setIndent($global_indent)
    {
        if (!is_int($global_indent)) {
            throw new \InvalidArgumentException('Global indent must be int');
        }
        if ($global_indent < 0) {
            $global_indent = 0;
        }
        $this->global_indent += $global_indent;
    }

    /**
     * 再写入一行代码，并且这行代码需要缩进
     * @param string $str 代码
     * @return $this
     */
    public function pushIndent($str)
    {
        $str = self::indentSpace(1) . $str;
        return $this->pushStr($str);
    }

    /**
     * 写入完整一行代码
     * @param string $str 代码
     * @return $this
     */
    public function pushStr($str)
    {
        $line_str = self::indentSpace($this->indent) . $str;
        $this->line_buffer[] = $line_str;
        return $this;
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
            throw new Exception('Can not push self to self');
        }
        //位置
        $index = count($this->line_buffer);
        $sub_buf->setIndent($this->indent);
        $this->sub_buffer_list[$index] = $sub_buf;
        //将该位置用空字符串占用
        $this->line_buffer[] = '';
        return $this;
    }

    /**
     * 空行
     * @return $this
     */
    public function emptyLine()
    {
        $this->line_buffer[] = '';
        return $this;
    }

    /**
     * 增加缩进
     * @return $this
     */
    public function indent()
    {
        $this->indent++;
        return $this;
    }

    /**
     * 减少缩进
     * @return $this
     */
    public function backIndent()
    {
        if (--$this->indent < 0) {
            $this->indent = 0;
        }
        return $this;
    }

    /**
     * 生成缩进
     * @param int $indent 缩进次数
     * @return string
     */
    public static function indentSpace($indent)
    {
        static $cache_arr = [];
        if (isset($cache_arr[$indent])) {
            return $cache_arr[$indent];
        }
        $result = str_repeat(self::$indent_space, $indent);
        $cache_arr[$indent] = $result;
        return $result;
    }

    /**
     * 设置缩进字符
     * @param string $indent_str
     */
    public static function setIndentSpace($indent_str)
    {
        self::$indent_space = $indent_str;
    }

    /**
     * 设置第一行自动空行
     */
    public function setPrefixEmptyLine()
    {
        $this->prefix_empty_line = true;
    }

    /**
     * 输出内容
     * @return string
     */
    public function dump()
    {
        if ($this->isEmpty()) {
            return '';
        }
        if (!empty($this->sub_buffer_list)) {
            $this->mergeSubBuffer();
        }
        //每一行强制缩进
        if ($this->global_indent > 0) {
            $prefix_str = self::indentSpace($this->global_indent);
            foreach ($this->line_buffer as &$each_str) {
                if (false === strpos($each_str, PHP_EOL)) {
                    $each_str = $prefix_str . $each_str;
                } else {
                    $tmp_lines = explode(PHP_EOL, $each_str);
                    foreach ($tmp_lines as &$tmp_line) {
                        $tmp_line = $prefix_str . $tmp_line;
                    }
                    $each_str = join(PHP_EOL, $tmp_lines);
                }
            }
        }
        $result = '';
        if ($this->prefix_empty_line) {
            $result .= PHP_EOL;
        }
        $result .= join(PHP_EOL, $this->line_buffer);
        $this->clean();
        return $result;
    }

    /**
     * 将子buffer和主内容合并
     */
    private function mergeSubBuffer()
    {
        foreach ($this->sub_buffer_list as $index => $sub_buffer) {
            //如果是空的，不能占一行
            if ($sub_buffer->isEmpty()) {
                unset($this->line_buffer[$index]);
            } else {
                //将该位置替换成应该有的字符串
                $this->line_buffer[$index] = $sub_buffer->dump();
            }
        }
    }

    /**
     * 清空
     */
    public function clean()
    {
        $this->line_buffer = [];
        $this->indent = $this->global_indent = 0;
    }

    /**
     * 是否是空的buf
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->line_buffer);
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

    /**
     * 写入一行代码，并锁定，不允许再写入第二次
     * @param string $code
     */
    public function pushUniqueStr($code)
    {
        if (isset($this->code_lock[$code])) {
            return;
        }
        $this->code_lock[$code] = true;
        $this->pushStr($code);
    }
}
