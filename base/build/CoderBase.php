<?php

namespace ffan\dop\build;

use ffan\dop\Exception;
use ffan\dop\Builder;
use ffan\dop\protocol\Struct;

/**
 * Class CoderBase 各语言基类
 * @package ffan\dop\build
 */
abstract class CoderBase
{
    /**
     * @var Builder
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
     * CoderBase constructor.
     * @param Builder $generator
     * @param string $name
     */
    public function __construct(Builder $generator, $name)
    {
        $this->generator = $generator;
        $this->build_opt = $generator->getBuildOption();
        $this->code_name = $name;
    }

    /**
     * 生成通用的文件
     * @param FileBuf $file_buf
     * @return void
     */
    public function buildCommonCode(FileBuf $file_buf)
    {
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     * @return void
     */
    public function buildStructCode($struct, FileBuf $file_buf)
    {
    }

    /**
     * 按协议命名空间生成代码
     * @param string $name_space
     * @param FileBuf $file_buf
     * @return void
     */
    public function buildNsCode($name_space, FileBuf $file_buf)
    {
    }

    /**
     * 生成 数据打包， 解包方法
     * @param FileBuf $file_buf
     * @param Struct $struct
     */
    protected function packMethodCode($file_buf, $struct)
    {
        $pack_type = $this->build_opt->pack_type;
        //json方式
        if ($pack_type & BuildOption::PACK_TYPE_JSON) {
            $json_pack = $this->getPackInstance('json');
            if (null !== $json_pack) {
                $this->writePackCode($struct, $file_buf, $json_pack);
            }
        }
        //msgpack
        if ($pack_type & BuildOption::PACK_TYPE_MSGPACK) {
            $msg_pack = $this->getPackInstance('msgPack');
            if (null !== $msg_pack) {
                $this->writePackCode($struct, $file_buf, $msg_pack);
            }
        }
        //binary
        if ($pack_type & BuildOption::PACK_TYPE_BINARY) {
            $bin_pack = $this->getPackInstance('binary');
            if (null !== $bin_pack) {
                $this->writePackCode($struct, $file_buf, $bin_pack);
            }
        }
    }

    /**
     * 写入pack代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     * @param PackerBase $packer
     * @param array $require_arr 用于防止循环依赖
     * @throws Exception
     */
    private function writePackCode($struct, FileBuf $file_buf, PackerBase $packer, array &$require_arr = [])
    {
        $code_buf = $file_buf->getBuf(FileBuf::METHOD_BUF);
        if (null === $code_buf) {
            return;
        }
        //将依赖的packer写入
        $require = $packer->getRequirePacker();
        if (is_array($require)) {
            foreach ($require as $name) {
                if (isset($require_arr[$name])) {
                    throw new Exception('Cycle require detect. ' . $name);
                }
                $require_arr[$name] = true;
                $req_pack = $this->getPackInstance($name);
                if ($require_arr) {
                    $this->writePackCode($struct, $file_buf, $req_pack, $require_arr);
                }
            }
        }
        $struct_type = $struct->getType();
        if ($this->isBuildPackMethod($struct_type)) {
            //防止两次生成相同方法
            $unique_flag = get_class($packer) . '::pack';
            if ($code_buf->addUniqueFlag($unique_flag)) {
                $packer->buildPackMethod($struct, $code_buf);
            }
        }
        if ($this->isBuildUnpackMethod($struct_type)) {
            //防止两次生成相同方法
            $unique_flag = get_class($packer) . '::unpack';
            if ($code_buf->addUniqueFlag($unique_flag)) {
                $packer->buildUnpackMethod($struct, $code_buf);
            }
        }
    }

    /**
     * 获取
     * @param string $pack_type
     * @return PackerBase
     * @throws Exception
     */
    public function getPackInstance($pack_type)
    {
        if (isset($this->pack_instance_arr[$pack_type])) {
            return $this->pack_instance_arr[$pack_type];
        }
        $class_name = ucfirst($pack_type) . 'Pack';
        $file = dirname(__DIR__) . '/pack/' . $this->code_name . DIRECTORY_SEPARATOR . $class_name . '.php';
        //文件不存在
        if (!is_file($file)) {
            throw new Exception('Can not find file:' . $file);
        }
        /** @noinspection PhpIncludeInspection */
        require_once $file;
        $ns = 'ffan\dop\pack\\' . $this->code_name . '\\';
        $full_class_name = $ns . $class_name;
        if (!class_exists($full_class_name)) {
            throw new Exception('Can not load class ' . $full_class_name);
        }
        $parents = class_parents($full_class_name);
        if (!isset($parents['ffan\dop\PackerBase'])) {
            throw new Exception('Class ' . $full_class_name . ' must extend of PackerBase');
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
