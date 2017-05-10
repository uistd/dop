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
     * @var array 变量列表
     */
    private $variable_arr;

    /**
     * @var string 相对路径
     */
    private $path;

    /**
     * FileBuf constructor.
     * @param null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->file_name = $name;
    }

    /**
     * 设置路径
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
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
     * 获取文件全名
     */
    public function getFullName()
    {
        if (null === $this->path) {
            throw new Exception('Path is null');
        }
        return $this->path . '/' . $this->file_name;
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
     * 生成一个具名CodeBuf，并将buf insert
     * @param string $name
     * @return CodeBuf
     * @throws Exception
     */
    public function touchBuf($name)
    {
        if (isset($this->buf_arr[$name])) {
            throw new Exception('Code buf name "' . $name . '" conflict, file:' . $this->file_name);
        }
        $buf = new CodeBuf();
        $this->buf_arr[$name] = $buf;
        $this->insertBuf($buf);
        return $buf;
    }

    /**
     * 生成一个具名StrBuf
     * @param string $name
     * @return StrBuf
     * @throws Exception
     */
    public function touchStrBuf($name)
    {
        if (isset($this->buf_arr[$name])) {
            throw new Exception('Code buf name "' . $name . '" conflict, file:' . $this->file_name);
        }
        $buf = new StrBuf();
        $this->buf_arr[$name] = $buf;
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

    /**
     * 插入一个带key的子buf
     * @param string $name
     * @param BufInterface $buf
     * @throws Exception
     */
    public function insertNameBuf($name, BufInterface $buf)
    {
        if (isset($this->buf_arr[$name])) {
            throw new Exception('InsertNameBuf name "' . $name . '" conflict, file:' . $this->file_name);
        }
        $this->buf_arr[$name] = $buf;
        $this->insertBuf($buf);
    }

    /**
     * 设置一个变量，一个变量名可以对应多个buf
     * @param string $name
     * @param BufInterface $buf
     * @throws Exception
     */
    public function addVariable($name, BufInterface $buf)
    {
        if (!isset($this->variable_arr[$name])) {
            $this->variable_arr[$name] = array();
        }
        $this->variable_arr[$name][] = $buf;
    }

    /**
     * 设置变量的值
     * @param string $name
     * @param string|BufInterface $value
     */
    public function setVariableValue($name, $value)
    {
        if (!isset($this->variable_arr[$name])) {
            return;
        }
        /** @var BufInterface $each_buf */
        foreach ($this->variable_arr[$name] as $each_buf) {
            $each_buf->push($value);
        }
    }

    /**
     * 将内容写入一个指定的buf
     * @param string $name
     * @param string|BufInterface $content
     * @return bool 写入成功 返回 true，如果 buf不存在，返回 false
     */
    public function pushToBuf($name, $content)
    {
        if (!isset($this->buf_arr[$name])) {
            return false;
        }
        $this->buf_arr[$name]->push($content);
        return true;
    }
}
