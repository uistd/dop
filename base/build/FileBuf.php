<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class FileBuf 一个文件的代码
 * @package ffan\dop\build
 */
class FileBuf extends CodeBuf
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
     * 生成一个带名字的buf
     * @param string $name
     * @return CodeBuf
     * @throws Exception
     */
    public function touchBuf($name)
    {
        if (isset($this->buf_arr[$name])) {
            throw new Exception('Add buf name "' . $name . '" conflict, file:' . $this->file_name);
        }
        $buf = new CodeBuf();
        $this->buf_arr[$name] = $buf;
        $this->insertBuf($buf);
        return $buf;
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
}
