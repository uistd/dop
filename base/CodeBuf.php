<?php

namespace ffan\dop;

/**
 * Class CodeBuf
 * @package ffan\dop
 */
class CodeBuf
{
    /**
     * 代码片断
     */
    const BUF_TYPE_CODE = 1;

    /**
     * 方法 代码
     */
    const BUF_TYPE_FUNCTION = 2;

    /**
     * CLASS 代码
     */
    const BUF_TYPE_CLASS = 3;

    /**
     * 整个 文件代码
     */
    const BUF_TYPE_FILE = 4;

    /**
     * @var int 缩进级别
     */
    private $indent = 0;

    /**
     * @var array 内容缓存
     */
    private $str_buffer = [];

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
     * @var int 存放的代码类型
     */
    private $buf_type = self::BUF_TYPE_CODE;

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
     * 设置代码类型
     * @param int $type
     */
    public function setBufType($type)
    {
        $this->buf_type = (int)$type;
    }

    /**
     * 设置代码类型
     * @return int
     */
    public function getBufType()
    {
        return $this->buf_type;
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
        $this->str_buffer[] = $line_str;
        return $this;
    }

    /**
     * 直接写入一个Buffer，输出的时候，会先将buffer的内容输出
     * @param CodeBuf $buffer
     * @param bool $is_refer 是否是引用
     * 如果是引用，该buffer的变更将继续生效
     * 如果不是引用，直接将该buffer当前的值导出
     * @return $this
     */
    public function pushBuffer(CodeBuf $buffer, $is_refer = false)
    {
        //如果是引用，要记录当前的缩进、当前的代码位置，输出的时候根据这两个属性将buffer里的内容输出到正确的位置
        if ($is_refer) {
            //位置
            $index = count($this->str_buffer);
            //当前缩进
            $current_indent = $this->indent;
            $sub_buffer_arr = array(
                $current_indent, $buffer
            );
            $this->sub_buffer_list[$index] = $sub_buffer_arr;
            //将该位置用空字符串占用
            $this->str_buffer[] = '';
        } else {
            //将传入buffer的所有内容都合并到当前的buffer，从此不再有任何关系
            $line_arr = $buffer->getLineArr();
            foreach ($line_arr as $each_line) {
                $this->push($each_line);
            }
        }
        return $this;
    }

    /**
     * 空行
     * @return $this
     */
    public function emptyLine()
    {
        $this->str_buffer[] = '';
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
            foreach ($this->str_buffer as &$each_str) {
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
        $result = join(PHP_EOL, $this->str_buffer);
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
        return $this->str_buffer;
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
            //将该位置替换成应该有的字符串
            $this->str_buffer[$index] = $tmp_buffer->dump($tmp_indent);
        }
    }

    /**
     * 清空
     */
    public function clean()
    {
        $this->str_buffer = [];
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
}
