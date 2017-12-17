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
     * @var array 附加消息
     */
    private static $stack_arr = array();

    /**
     * push栈消息
     * @param $trace_msg
     */
    public static function pushStack($trace_msg)
    {
        self::$stack_arr[] = $trace_msg;
    }

    /**
     * pop栈消息
     */
    public static function popStack()
    {
        array_pop(self::$stack_arr);
    }

    /**
     * DOPException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (!empty(self::$stack_arr)) {
            $tmp_arr = array();
            foreach (self::$stack_arr as $i => $stack) {
                $tmp_arr[] = '[BUILD TRACE #' . $i . ']';
                $tmp_arr[] = $stack;
            }
            $message = join(PHP_EOL, $tmp_arr) . ', ' . $message;
        }
        $message .= PHP_EOL;
        $message .= PHP_EOL . Debug::codeTrace();
        parent::__construct($message, $code, $previous);
    }
}
