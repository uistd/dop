<?php

namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class FileBuf 一个文件的代码
 * @package ffan\dop\build
 */
class FileBuf
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
    public $main_buf;

    /**
     * @var string 文件名
     */
    private $file_name;

    /**
     * @var string 文件相对路径
     */
    private $relate_path = '';

    /**
     * GroupCodeBuf constructor.
     */
    public function __construct()
    {
        $this->main_buf = new CodeBuf();
    }

    /**
     * 设置文件相对路径
     * @param string $path
     */
    public function setRelatePath($path)
    {
        $this->relate_path = $path;
    }

    /**
     * 获取文件相对路径
     * @return string
     */
    public function getRelatePath()
    {
        return $this->relate_path;
    }

    /**
     * 设置文件名
     * @param string $file_name
     */
    public function setFileName($file_name)
    {
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
            $this->main_buf->pushBuffer($buf, true);
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
     * 获取文件的内容
     * @return string
     */
    public function getContent()
    {
        return $this->main_buf->dump();
    }
}
