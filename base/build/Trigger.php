<?php
namespace ffan\dop\build;
use ffan\php\utils\Str as FFanStr;

/**
 * Class Trigger 触发器
 * @package ffan\dop\build
 */
abstract class Trigger extends NodeBase
{
    /**
     * 类型：埋buf
     */
    const TYPE_BUF = 'buf';

    /**
     * @var string 类型
     */
    protected $type;

    /**
     * 获取类型
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @var array 生效的coder
     */
    private $coder_list;

    /**
     * @var array 生效的packer
     */
    private $packer_list;

    /**
     * @var int 方法类型
     */
    private $method_type;

    /**
     * 全局初始化
     * @param \DomElement $node
     * @return void
     */
    public function init($node)
    {
        $coder_set = self::read($node, 'coder');
        if (null !== $coder_set) {
            $this->coder_list = FFanStr::split($coder_set);
        }

        $packer_set = self::read($node, 'packer');
        if (null !== $packer_set) {
            $this->packer_list = FFanStr::split($packer_set);
        }
        $method_type = self::read($node, 'method');
        if ('unpack' === $method_type) {
            $this->method_type = PackerBase::METHOD_UNPACK;
        } else {
            $this->method_type = PackerBase::METHOD_PACK;
        }
        $this->parse($node);
    }

    /**
     * 触发
     * @param CodeBuf $buf
     * @param FileBuf $file
     * @param PackerBase $packer
     */
    public function trigger(CodeBuf $buf, FileBuf $file, PackerBase $packer)
    {
        if (null !== $this->coder_list) {
            $coder_name = $packer->getCoder()->getName();
            if (!in_array($coder_name, $this->coder_list)) {
                return;
            }
        }
        if (null !== $this->packer_list) {
            $packer_name = $packer->getName();
            if (!in_array($packer_name, $this->packer_list)) {
                return;
            }
        }
        if (null !== $this->method_type && $this->method_type !== $packer->getCurrentMethod()) {
            return;
        }
        $this->onTrigger($buf, $file, $packer);
    }

    /**
     * 初始化
     * @param $node
     * @return void
     */
    abstract public function parse($node);

    /**
     * 触发
     * @param CodeBuf $buf
     * @param FileBuf $file
     * @param PackerBase $packer
     * @return void
     */
    abstract public function onTrigger(CodeBuf $buf, FileBuf $file, PackerBase $packer);
}
