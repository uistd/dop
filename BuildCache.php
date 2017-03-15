<?php
namespace ffan\dop;

use ffan\php\utils\Utils as FFanUtils;

/**
 * Class BuildCache 编译缓存
 * @package ffan\dop
 */
class BuildCache
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
     * @var ProtocolManager
     */
    private $manager;

    /**
     * @var bool 是否支持msg_pack
     */
    private $support_msg_pack;

    /**
     * @var bool 是否支持ig binary
     */
    private $support_ig_binary;

    /**
     * @var string 验证缓存是否可用的key
     */
    private $private_key;

    /**
     * BuildCache constructor.
     * @param ProtocolManager $manager
     * @param array $config
     */
    public function __construct(ProtocolManager $manager, $config)
    {
        $this->manager = $manager;
        //直接使用配置作key，配置发生变化的时候，缓存失效
        $this->private_key = md5(serialize($config));
    }

    /**
     * 加载缓存
     * @param string $name
     * @return null|array
     */
    public function loadCache($name)
    {
        $cache_file = $this->cacheFileName($name);
        if (!is_file($cache_file)) {
            $this->manager->buildLogNotice('不存在缓存文件：' . $name);
            return null;
        }
        $contents = file_get_contents($cache_file);
        if (!$contents) {
            return null;
        }
        $cache_data = $this->unpack($contents);
        if (!is_array($cache_data)) {
            $this->manager->buildLogNotice('缓存失效' . $name);
            return null;
        }
        return $cache_data;
    }

    /**
     * 生成缓存
     * @param string $name
     * @param array $data
     */
    public function saveCache($name, array $data)
    {
        $cache_file = $this->cacheFileName($name);
        $content = $this->pack($data);
        $re = file_put_contents($cache_file, $content);
        if (false === $re) {
            $this->manager->buildLogError('写入缓存' . $name . '失败');
        } else {
            $this->manager->buildLog('生成缓存：' . $name);
        }
    }

    /**
     * 返回缓存文件名
     * @param string $name
     * @return string
     */
    private function cacheFileName($name)
    {
        $path = FFanUtils::fixWithRuntimePath('');
        return FFanUtils::joinFilePath($path, $name . '.cache');
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
        return $this->unpack($result[self::DATA_KEY]);
    }

    /**
     * 序列化数据
     * @param array $data
     * @return string
     */
    private function encode($data)
    {
        if ($this->isSupportIgBinary()) {
            return igbinary_serialize($data);
        } elseif ($this->isSupportMsgPack()) {
            return msgpack_pack($data);
        } else {
            return serialize($data);
        }
    }

    /**
     * 反序列化数据
     * @param string $raw_data
     * @return mixed
     */
    private function decode($raw_data)
    {
        if ($this->isSupportIgBinary()) {
            return igbinary_unserialize($raw_data);
        } elseif ($this->isSupportMsgPack()) {
            return msgpack_unpack($raw_data);
        } else {
            return unserialize($raw_data);
        }
    }

    /**
     * 是否支持msg pack
     * @return bool
     */
    private function isSupportMsgPack()
    {
        if (null === $this->support_msg_pack) {
            $this->support_msg_pack = extension_loaded('msgpack');
        }
        return $this->support_msg_pack;
    }

    /**
     * 是否支持IG binary
     * @return bool
     */
    private function isSupportIgBinary()
    {
        if (null === $this->support_ig_binary) {
            $this->support_ig_binary = extension_loaded('igbinary');
        }
        return $this->support_ig_binary;
    }
}
