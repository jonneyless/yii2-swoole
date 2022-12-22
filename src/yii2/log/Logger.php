<?php

namespace ijony\yiis\yii2log;

use Throwable;

defined('YII2_SWOOLE_PATH') or define('YII2_SWOOLE_PATH', dirname(__DIR__));

/**
 * Class Log
 *
 * @package ijony\yiis\yii2log
 */
class Logger extends \yii\log\Logger
{

    public function log($message, $level, $category = 'application')
    {
        $time = microtime(true);
        $traces = [];
        if ($this->traceLevel > 0) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line'])
                    && strpos($trace['file'], YII2_PATH) !== 0
                    && strpos($trace['file'], YII2_SWOOLE_PATH) !== 0) {
                    //cli remove start script
                    if (isset($trace['class']) && $trace['class'] == 'ijony\yiis\yii2server\Server') {
                        break;
                    }
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->traceLevel) {
                        break;
                    }
                }
            }
        }
        // exceptions may not be serializable if in the call stack somewhere is a Closure
        if ($message instanceof Throwable) {
            $message = (string) $message;
        }
        $this->messages[] = [$message, $level, $category, $time, $traces, memory_get_usage()];
        $isErr = $level == \yii\log\Logger::LEVEL_ERROR;
        if (($this->flushInterval > 0 && count($this->messages) >= $this->flushInterval) || $isErr) {
            $this->flush($isErr);
        }
    }
}