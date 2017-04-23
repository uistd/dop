<?php

namespace ffan\dop;

use Throwable;

/**
 * Class Exception
 * @package ffan\dop
 */
class Exception extends \Exception
{
    /**
     * @var string 附加消息
     */
    private static $append_msg = array();

    /**
     * 设置附加消息
     * @param $trace_msg
     */
    public static function setAppendMsg($trace_msg)
    {
        self::$append_msg = $trace_msg;
    }

    /**
     * DOPException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (!empty(self::$append_msg)) {
            $message = self::$append_msg . ', ' . $message;
        }
        parent::__construct($message, $code, $previous);
    }
}
