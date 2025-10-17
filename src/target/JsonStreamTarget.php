<?php

namespace Sil\JsonLog\target;

use codemix\streamlog\Target as Streamlog;
use Sil\JsonLog\JsonLogHelper;

class JsonStreamTarget extends Streamlog
{
    /**
     * Format a log message as a string of JSON.
     *
     * @param array $logMessageData The array of log data provided by Yii. See
     *     `\yii\log\Logger::messages`.
     * @return string The JSON-encoded log data.
     */
    public function formatMessage($logMessageData)
    {
        return JsonLogHelper::formatAsJson(
            $logMessageData,
            $this->getMessagePrefix($logMessageData)
        );
    }
}
