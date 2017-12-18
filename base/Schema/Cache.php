<?php

namespace UiStd\Dop\Schema;

use UiStd\Common\Utils;

/**
 * Class Cache 编译缓存
 * @package UiStd\Dop\build
 */
class Cache
{
    /**
     * 签名key
     */
    const SIGN_KEY = 'sign';

    /**
     * 数据key
     */
    const DATA_KEY = 'data';

    /**
     * @var string 验证缓存是否可用的key
     */
    private $sign_key;

    /**
     * @var string 代码生成目录
     */
    private $file_name;

    /**
     * @var array
     */
    private $data;

    /**
     * BuildCache constructor.
     * @param string $sign_key 数据检验码
     * @param string $file_name
     */
    public function __construct($sign_key, $file_name)
    {
        //直接使用配置作key，配置发生变化的时候，缓存失效
        $this->sign_key = $sign_key;
        $this->file_name = $file_name;
        $this->init();
    }

    /**
     * 初始化
     */
    private function init()
    {
        if (!is_file($this->file_name)) {
            return;
        }
        $contents = file_get_contents($this->file_name);
        if (!$contents) {
            return;
        }
        $cache_data = $this->unpack($contents);
        if (!is_array($cache_data)) {
            return;
        }
        $this->data = $cache_data;
    }

    /**
     * 缓存是否可用
     */
    public function isAvailable()
    {
        return null !== $this->data;
    }

    /**
     * 设置缓存
     * @param string $name
     * @param mixed $data
     */
    public function set($name, $data)
    {
        $this->data[$name] = $data;
    }

    /**
     * 移除缓存
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->data[$name]);
    }

    /**
     * 保存
     * @return bool
     */
    public function save()
    {
        if (empty($this->data)) {
            $this->data = array();
        }
        $content = $this->pack($this->data);
        Utils::pathWriteCheck(dirname($this->file_name));
        return file_put_contents($this->file_name, $content);
    }

    /**
     * 打包数据
     * @param array $data
     * @return string
     */
    private function pack($data)
    {
        $raw_data = $this->encode($data);
        $pack_data = array(
            self::DATA_KEY => $raw_data,
            self::SIGN_KEY => md5($raw_data)
        );
        $raw_data = $this->encode($pack_data);
        $zip_data = gzcompress($raw_data);
        return base64_encode($zip_data);
    }

    /**
     * 解包数据
     * @param $contents
     * @return null|array
     */
    private function unpack($contents)
    {
        $raw_data = base64_decode($contents);
        if (false === $raw_data) {
            return null;
        }
        //解压
        $raw_data = gzuncompress($raw_data);
        if (false === $raw_data) {
            return null;
        }
        $result = $this->decode($raw_data);
        if (!is_array($result)) {
            return null;
        }
        //数据出错
        if (!isset($result[self::SIGN_KEY], $result[self::DATA_KEY])) {
            return null;
        }
        //数据签名不可用
        if (md5($result[self::DATA_KEY]) !== $result[self::SIGN_KEY]) {
            return null;
        }
        return $this->decode($result[self::DATA_KEY]);
    }

    /**
     * 序列化数据
     * @param array $data
     * @return string
     */
    private function encode($data)
    {
        if (function_exists('igbinary_serialize')) {
            return igbinary_serialize($data);
        } else {
            return json_encode($data);
        }
    }

    /**
     * 反序列化数据
     * @param string $raw_data
     * @return mixed
     */
    private function decode($raw_data)
    {
        if (function_exists('igbinary_unserialize')) {
            return igbinary_unserialize($raw_data);
        } else {
            return json_decode($raw_data, true);
        }
    }
}
