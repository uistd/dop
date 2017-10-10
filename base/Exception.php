<?php

namespace FFan\Dop;

use FFan\Std\Console\Debug;
use Throwable;

/**
 * Class Exception
 * @package FFan\Dop
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
        $current_struct = Manager::getCurrentStruct();
        if (null !== $current_struct) {
            $message .= PHP_EOL. $current_struct->C14N();
        }
        $message .= PHP_EOL .Debug::codeTrace();
        parent::__construct($message, $code, $previous);
    }
}
