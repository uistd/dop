<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class CodeBuf
 * @package ffan\dop\build
 */
class CodeBuf
{
    /**
     * 子buf类型
     */
    const SUB_BUF_TYPE_CODE = 1;
    const SUB_BUF_TYPE_STR =2;
    
    /**
     * @var int 缩进级别
     */
    private $indent = 0;

    /**
     * @var array 内容缓存
     */
    private $line_buffer = [];

    /**
     * @var string 缩进
     */
    private $indent_space;

    /**
     * @var string 临时一行代码
     */
    private $tmp_line_str;

    /**
     * @var array 子buffer 数据
     */
    private $sub_buffer_list;

    /**
     * @var array 唯一标志列表，用于限制同一个类不能有同名方法等
     */
    private $unique_flag_arr;

    /**
     * CodeBuf constructor.
     * @param bool $blank_indent 是否使用空格替代缩进
     */
    public function __construct($blank_indent = true)
    {
        if ($blank_indent) {
            $this->indent_space = '    ';
        } else {
            $this->indent_space = "\t";
        }
    }

    /**
     * 再写入一行代码，并且这行代码需要缩进
     * @param string $str 代码
     * @return $this
     */
    public function pushIndent($str)
    {
        $str = $this->indent_space . $str;
        return $this->push($str);
    }

    /**
     * 写入完整一行代码
     * @param string $str 代码
     * @return $this
     */
    public function push($str)
    {
        $line_str = $this->indentSpace() . $str;
        $this->line_buffer[] = $line_str;
        return $this;
    }

    /**
     * 直接写入一个Buffer，输出的时候，会先将buffer的内容输出
     * @param CodeBuf $code_buf
     * @return $this
     * @throws Exception
     */
    public function pushCodeBuf(CodeBuf $code_buf)
    {
        if ($code_buf === $this) {
            throw new Exception('Can not push self to self');
        }
        //位置
        $index = count($this->line_buffer);
        //当前缩进
        $current_indent = $this->indent;
        $sub_buffer_arr = array(
            $current_indent, $code_buf, self::SUB_BUF_TYPE_CODE
        );
        $this->sub_buffer_list[$index] = $sub_buffer_arr;
        //将该位置用空字符串占用
        $this->line_buffer[] = '';
        return $this;
    }

    /**
     * 插入一行，这一行的内容是str buf，在dump的时候会先 dump出该str的内容
     * @param StrBuf $str_buf
     * @return $this
     */
    public function pushStrBuf(StrBuf $str_buf)
    {
        //位置
        $index = count($this->line_buffer);
        //当前缩进
        $current_indent = $this->indent;
        $sub_buffer_arr = array(
            $current_indent, $str_buf, self::SUB_BUF_TYPE_STR
        );
        $this->sub_buffer_list[$index] = $sub_buffer_arr;
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
     * 结束一行
     * @return $this
     */
    public function lineFin()
    {
        $this->push($this->tmp_line_str);
        $this->tmp_line_str = '';
        return $this;
    }

    /**
     * 生成一行临时代码
     * @param string $str
     * @return $this
     */
    public function lineTmp($str)
    {
        $this->tmp_line_str .= $str;
        return $this;
    }

    /**
     * 增加缩进
     * @return $this
     */
    public function indentIncrease()
    {
        $this->indent++;
        return $this;
    }

    /**
     * 减少缩进
     * @return $this
     */
    public function indentDecrease()
    {
        if (--$this->indent < 0) {
            $this->indent = 0;
        }
        return $this;
    }

    /**
     * 生成缩进
     * @return string
     */
    private function indentSpace()
    {
        static $indent, $result;
        if ($this->indent === $indent) {
            return $result;
        }
        $indent = $this->indent;
        $result = str_repeat($this->indent_space, $this->indent);
        return $result;
    }

    /**
     * 输出前准备
     * @param int $force_indent 每一行强制增加缩进
     */
    private function beforeOutPut($force_indent)
    {
        if (!empty($this->sub_buffer_list)) {
            $this->mergeSubBuffer();
        }
        //每一行强制缩进
        if ($force_indent > 0) {
            $this->indent = $force_indent;
            $prefix_str = $this->indentSpace();
            foreach ($this->line_buffer as &$each_str) {
                $each_str = $prefix_str . $each_str;
            }
        }
    }

    /**
     * 输出内容
     * @param int $force_indent 每一行强制增加缩进
     * @return string
     */
    public function dump($force_indent = 0)
    {
        $this->beforeOutPut($force_indent);
        $result = join(PHP_EOL, $this->line_buffer);
        $this->clean();
        return $result;
    }

    /**
     * 获取所有的行
     * return array
     */
    public function getLineArr()
    {
        $this->beforeOutPut(0);
        return $this->line_buffer;
    }

    /**
     * 将子buffer和主内容合并
     */
    private function mergeSubBuffer()
    {
        foreach ($this->sub_buffer_list as $index => $arr) {
            $tmp_indent = $arr[0];
            /** @var CodeBuf $tmp_buffer */
            $tmp_buffer = $arr[1];
            //@tobe continue
            $sub_type = $arr[2];
            //如果是空的，不能占一行
            if ($tmp_buffer->isEmpty()) {
                unset($this->line_buffer[$index]);
            } else {
                //将该位置替换成应该有的字符串
                $this->line_buffer[$index] = $tmp_buffer->dump($tmp_indent);
            }
        }
    }

    /**
     * 清空
     */
    public function clean()
    {
        $this->line_buffer = [];
        $this->tmp_line_str = '';
        $this->indent = 0;
    }

    /**
     * 加入唯一标志
     * @param string $flag
     * @return bool
     */
    public function addUniqueFlag($flag)
    {
        if (isset($this->unique_flag_arr[$flag])) {
            return false;
        }
        $this->unique_flag_arr[$flag] = true;
        return true;
    }

    /**
     * 是否是空的buf
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->line_buffer);
    }
}
