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
     * FileBuf constructor.
     * @param null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->file_name = $name;
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
     * 加载一个模板
     * @param string $tpl_name 模板名称，相对于 Coder的目录
     * @param null|array $data 模板上的变量名 
     */
    public function loadTpl($tpl_name, $data = null)
    {
    }
}
