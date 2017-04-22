<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class FileBuf 一个文件的代码
 * @package ffan\dop\build
 */
class FileBuf implements BufInterface
{
    /**
     * 一些内置的常用buf
     */
    const PROPERTY_BUF = 'property';
    const METHOD_BUF = 'method';
    const HEADER_BUF = 'header';
    const IMPORT_BUF = 'import';

    /**
     * @var array
     */
    private $buf_arr;

    /**
     * @var CodeBuf 主buf
     */
    private $main_buf;

    /**
     * @var string 文件名，包含相对于代码生成目录的相对路径
     */
    private $file_name;

    /**
     * @var string 备注信息
     */
    private $remark;

    /**
     * GroupCodeBuf constructor.
     * @param string $file_name 文件名
     * @param string $remark
     */
    public function __construct($file_name, $remark = '')
    {
        $this->main_buf = new CodeBuf();
        $this->remark = $remark;
        $this->file_name = $file_name;
    }

    /**
     * 获取文件名
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * 获取文件的备注信息
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * 添加一个buf
     * @param $name
     * @param CodeBuf $buf
     * @param bool $push_to_main 是否将这个buf加入到main buf 中
     * @throws Exception
     */
    public function addBuf($name, CodeBuf $buf, $push_to_main = true)
    {
        if (isset($this->buf_arr[$name])) {
            throw new Exception('Add buf name "' . $name . '" conflict, file:' . $this->file_name);
        }
        $this->buf_arr[$name] = $buf;
        if ($push_to_main) {
            $this->main_buf->insertBuf($buf);
        }
    }

    /**
     * 获取文件中的一个buf
     * @param string $name 获取一个buf
     * @return null|CodeBuf
     */
    public function getBuf($name)
    {
        if (isset($this->buf_arr[$name])) {
            return $this->buf_arr[$name];
        } else {
            return null;
        }
    }

    /**
     * 获取main code buf
     * @return CodeBuf
     */
    public function getMainBuf()
    {
        return $this->main_buf;
    }
    
    /**
     * 获取文件的内容
     * @return string
     */
    public function dump()
    {
        return $this->main_buf->dump();
    }

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty()
    {
        return $this->main_buf->isEmpty();
    }

    /**
     * 插入子buf
     * @param BufInterface $sub_buf
     * @return $this
     */
    public function insertBuf(BufInterface $sub_buf)
    {
        $this->main_buf->insertBuf($sub_buf);
        return $this;
    }

    /**
     * 设置缩进
     * @param int $indent
     * @return void
     */
    public function setIndent($indent)
    {
        $this->main_buf->setIndent($indent);
    }
}
