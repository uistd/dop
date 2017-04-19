<?php

namespace ffan\dop;

/**
 * Class LanCodeBase 各语言基类
 * @package ffan\dop
 */
abstract class CodeGeneratorBase implements GenerateInterface
{
    /**
     * @var DOPGenerator
     */
    protected $generator;

    /**
     * @var BuildOption
     */
    protected $build_opt;
    
    /** @var string 当前语言名称 */
    protected $code_name;

    /**
     * @var array 生成打包， 解角代码的对象
     */
    private $pack_instance_arr;

    /**
     * CodeGeneratorBase constructor.
     * @param DOPGenerator $generator
     * @throws DOPException
     */
    public function __construct(DOPGenerator $generator)
    {
        $this->generator = $generator;
        $this->build_opt = $generator->getBuildOption();
        if (null === $this->code_name) {
            throw new DOPException('Unknown code_name');
        }
    }

    /**
     * 生成文件开始
     * @return CodeBuf|null
     */
    public function generateBegin()
    {
        return null;
    }

    /**
     * 生成文件结束
     * @return CodeBuf|null
     */
    public function generateFinish()
    {
        return null;
    }

    /**
     * 按类名生成代码
     * @param Struct $struct
     * @return CodeBuf|null
     */
    public function generateByClass($struct)
    {
        return null;
    }

    /**
     * 按协议文件生成代码
     * @param string $xml_file
     * @return CodeBuf|null
     */
    public function generateByXml($xml_file)
    {
        return null;
    }

    /**
     * 合并插件生成的方法 到 类文件
     * @param CodeBuf $code_buf
     * @param string $class_name
     */
    protected function mergePluginFunction($code_buf, $class_name)
    {
        $code_arr = $this->generator->getClassPluginCodeAll($class_name);
        /**
         * @var string $plugin_name
         * @var CodeBuf $plugin_code_buf
         */
        foreach ($code_arr as $plugin_name => $plugin_code_buf) {
            if (CodeBuf::BUF_TYPE_FUNCTION === $plugin_code_buf->getBufType()) {
                $code_buf->pushBuffer($plugin_code_buf);
            }
        }
    }

    /**
     * 生成 数据打包， 解包方法
     * @param CodeBuf $code_buf
     * @param Struct $struct
     */
    protected function packMethodCode($code_buf, $struct)
    {
        $pack_type = $this->build_opt->pack_type;
        //json方式
        if ($pack_type & BuildOption::PACK_TYPE_JSON) {
            $json_pack = $this->getPackInstance('json');
        }
    }

    /**
     * 写入pack代码
     * @param Struct $struct
     * @param CodeBuf $code_buf
     * @param PackMethodBase $packer
     */
    public function writePackCode($struct, CodeBuf $code_buf, PackMethodBase $packer)
    {
        if ($this->isBuildPackMethod($this->build_opt->build_side)) {
            $packer->buildPackMethod($struct, $code_buf);
        }
    }

    /**
     * 获取
     * @param string $pack_type
     * @return PackMethodBase
     * @throws DOPException
     */
    public function getPackInstance($pack_type)
    {
        if (!isset($this->pack_instance_arr[$pack_type])) {
            return $this->pack_instance_arr[$pack_type];
        }
        $class_name = ucfirst($pack_type) .'Pack';
        $file = basename(__DIR__) .'pack/'.$this->code_name .'.php';
        //文件不存在
        if (!is_file($file)) {
            throw new DOPException('Can not find file:'. $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $ns = 'dop\\ffan\\pack\\'. $this->code_name .'\\';
        $full_class_name = $ns . $class_name;
        if (!class_exists($full_class_name)) {
            throw new DOPException('Can not load class '. $full_class_name);
        }
        $implements = class_implements($full_class_name);
        if (!isset($implements['ffan\\dop\\\PackInterface'])) {
            throw new DOPException('Class '. $full_class_name .' must be implements of PackInterface');
        }
        $this->pack_instance_arr[$pack_type] = new $full_class_name();
        return $this->pack_instance_arr[$pack_type];
    }

    /**
     * 是否需要生成Encode方法
     * @param int $type
     * @return bool
     */
    private function isBuildPackMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,服务端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            //如果是Request 客户端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return $result;
    }


    /**
     * 是否需要生成Decode方法
     * @param int $type
     * @return bool
     */
    private function isBuildUnpackMethod($type)
    {
        $result = false;
        switch ($type) {
            //如果是response,客户端生成
            case Struct::TYPE_RESPONSE:
                if (BuildOption::SIDE_CLIENT === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            //如果是Request 服务端生成
            case Struct::TYPE_REQUEST:
                if (BuildOption::SIDE_SERVER === $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            case Struct::TYPE_STRUCT:
                if (0 !== $this->build_opt->build_side) {
                    $result = true;
                }
                break;
            default:
                throw new \InvalidArgumentException('Unknown type');
        }
        return $result;
    }
}
