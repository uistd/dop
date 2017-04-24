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
    protected $builder;

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
     * @var array 注册的打包器
     */
    private $reg_packer;

    /**
     * CoderBase constructor.
     * @param Builder $builder
     * @param string $name
     */
    public function __construct(Builder $builder, $name)
    {
        $this->builder = $builder;
        $this->build_opt = $builder->getBuildOption();
        $this->code_name = $name;
    }

    /**
     * 生成通用的文件
     * @return void
     */
    public function buildCommonCode()
    {
    }

    /**
     * 按Struct生成代码
     * @param Struct $struct
     * @return void
     */
    public function buildStructCode($struct)
    {
    }

    /**
     * 按协议命名空间生成代码
     * @param string $name_space
     * @return void
     */
    public function buildNsCode($name_space)
    {
    }

    /**
     * 生成 数据打包， 解包方法
     * @param FileBuf $file_buf
     * @param Struct $struct
     */
    protected function packMethodCode($file_buf, $struct)
    {
        $packer_list = $this->build_opt->getPacker();
        $packer_object_arr = array();
        $this->getAllPackerObject($packer_list, $packer_object_arr);
        /**
         * @var string $name
         * @var PackerBase $packer
         */
        foreach ($packer_object_arr as $name => $packer) {
            Exception::setAppendMsg('Build packer '. $name);
            $this->writePackCode($struct, $file_buf, $packer);
        }
    }

    /**
     * 获取所有的packer，包括依赖的
     * @param array $packer_arr
     * @param array $packer_object_arr
     */
    private function getAllPackerObject($packer_arr, &$packer_object_arr)
    {
        foreach ($packer_arr as $packer_name) {
            if (isset($packer_object_arr[$packer_name])) {
                continue;
            }
            $packer_object = $this->getPackInstance($packer_name);
            if (null === $packer_object) {
                $this->builder->getManager()->buildLogError('Can not found packer of '. $packer_name);
                continue;
            }
            $packer_object_arr[$packer_name] = $packer_object;
            //依赖的其它packer
            $require_packer = $packer_object->getRequirePacker();
            if (!empty($require_packer)) {
                $this->getAllPackerObject($require_packer, $packer_object_arr);
            }
        }
    }

    /**
     * 写入pack代码
     * @param Struct $struct
     * @param FileBuf $file_buf
     * @param PackerBase $packer
     * @throws Exception
     */
    private function writePackCode($struct, FileBuf $file_buf, PackerBase $packer)
    {
        $code_buf = $file_buf->getBuf(FileBuf::METHOD_BUF);
        if (null === $code_buf) {
            return;
        }
        $struct_type = $struct->getType();
        if ($this->isBuildPackMethod($struct_type)) {
            $packer->buildPackMethod($struct, $code_buf);
        }
        if ($this->isBuildUnpackMethod($struct_type)) {
            $packer->buildUnpackMethod($struct, $code_buf);
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
        $ns = 'ffan\dop\coder\\' . $this->code_name . '\\';
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
    
    /**
     * 注册自定义数据打包器
     * @param string $name
     * @param string $class_file
     */
    public function registerPacker($name, $class_file)
    {
        if (!isset($this->reg_packer[$name])) {
            $this->builder->getManager()->buildLogError('Packer '. $name .' conflict');
            return;
        }
        $this->reg_packer[$name] = $class_file;
    }
}
