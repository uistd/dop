<?php
namespace ffan\dop\build;

use ffan\dop\Exception;

/**
 * Class BufTrigger
 * @package ffan\dop\build
 */
class BufTrigger extends Trigger
{
    /**
     * @var string
     */
    private $buf_name;

    /**
     * @var string
     */
    private $buf_type;

    /**
     * 初始化
     * @param \DomElement $node
     * @return void
     * @throws Exception
     */
    public function parse($node)
    {
        $buf_name = self::read($node, 'buf_name');
        if (empty($buf_name)) {
            throw new Exception('Trigger buf_name mission');
        }
        $this->buf_name = $buf_name;
        $this->buf_type = self::read($node, 'type', 'code');
    }

    /**
     * 触发
     * @param CodeBuf $buf
     * @param FileBuf $file
     * @param PackerBase $packer
     * @return void
     */
    public function onTrigger(CodeBuf $buf, FileBuf $file, PackerBase $packer)
    {
        if ('str' === $this->buf_type) {
            $new_buf = new StrBuf();
        } else {
            $new_buf = new CodeBuf();
        }
        $buf->push($new_buf);
        $file->setBuf($this->buf_name, $new_buf);
    }
}
