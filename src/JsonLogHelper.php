<?php

namespace Sil\JsonLog;

use yii\helpers\Json;
use yii\log\Logger;

class JsonLogHelper
{
    /**
     * Extract the message content data into a more suitable format for
     * JSON-encoding for the log.
     *
     * @param mixed $messageContent The message content, which could be a
     *     string, array, exception, or other data type.
     * @return mixed The extracted data.
     */
    protected function extractMessageContentData($messageContent)
    {
        $result = null;

        if ($messageContent instanceof \Exception) {

            // Handle log messages that are exceptions a little more
            // intelligently.
            //
            // NOTE: In our limited testing, this is never used. Apparently
            //       something is converting the exceptions to strings despite
            //       the statement at
            //       http://www.yiiframework.com/doc-2.0/yii-log-logger.html#$messages-detail
            //       that the data could be an exception instance.
            //
            $result = array(
                'code' => $messageContent->getCode(),
                'exception' => $messageContent->getMessage(),
            );

            if ($messageContent instanceof \yii\web\HttpException) {
                $result['statusCode'] = $messageContent->statusCode;
            }
        } elseif ($this->isMultilineString($messageContent)) {

            // Split multiline strings (such as a stack trace) into an array
            // for easier reading in the log.
            $result = explode("\n", $messageContent);
        } else {

            // Use anything else as-is.
            $result = $messageContent;
        }

        return $result;
    }

    /**
     * If the given prefix is a JSON string with key-value data, extract it as
     * an associative array. Otherwise return null.
     *
     * @param mixed $prefix The raw prefix string.
     * @return null|array
     */
    protected function extractPrefixKeyValueData($prefix)
    {
        $result = null;

        // If it has key-value data, as evidenced by the raw prefix string
        // being a JSON object (not JSON array), use it.
        if ($this->isJsonString($prefix) && substr($prefix, 0, 1) === '{') {
            $result = Json::decode($prefix);
        }

        return $result;
    }

    /**
     * Static helper function for JsonLogHelper->formatMessageAndPrefix(...).
     *
     * @param array $logMessageData The array of log data provided by Yii. See
     *     `\yii\log\Logger::messages`.
     * @param string $prefix The log prefix string.
     * @return string The JSON-encoded log data.
     */
    public static function formatAsJson($logMessageData, $prefix = null)
    {
        $jsonLogHelper = new JsonLogHelper();
        return $jsonLogHelper->formatMessageAndPrefix($logMessageData, $prefix);
    }

    /**
     * Format a log message as a string of JSON.
     *
     * @param array $logMessageData The array of log data provided by Yii. See
     *     `\yii\log\Logger::messages`.
     * @param string|null $prefix (Optional:) The log prefix string.
     * @return string The JSON-encoded log data.
     */
    public function formatMessageAndPrefix($logMessageData, $prefix = null)
    {
        // Retrieve the relevant pieces of data from the logger message data.
        list($messageContent, $level, $category) = $logMessageData;

        // Begin assembling the data that we will JSON-encode for the log.
        $logData = [];

        // If the prefix is already a JSON string, decode it (to avoid
        // double-encoding it below).
        $prefixData = $this->extractPrefixKeyValueData($prefix);

        // Only include the prefix data and/or raw prefix if there was content.
        if ($prefixData) {
            foreach ($prefixData as $key => $value) {
                $logData[$key] = $value;
            }
        } elseif ($prefix) {
            $logData['prefix'] = $prefix;
        }

        $logData['level'] = Logger::getLevelName($level);
        $logData['category'] = $category;
        $logData['message'] = $this->extractMessageContentData($messageContent);

        // Format the data as a JSON string and return it.
        return Json::encode($logData);
    }

    /**
     * Determine whether the given value is a string that parses as valid JSON.
     *
     * @param mixed $string The value to check.
     * @return boolean
     */
    protected function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        }

        $firstChar = substr($string, 0, 1);

        // If it starts the way a JSON object or array would, and parsing it as
        // JSON returns no errors, consider it a JSON string.
        if (($firstChar === '{') || ($firstChar === '[')) {
            json_decode($string);
            return json_last_error() == JSON_ERROR_NONE;
        }

        return false;
    }

    /**
     * Determine whether the given data is a string that contains at least one
     * line feed character ("\n").
     *
     * @param mixed $data The data to check.
     * @return boolean
     */
    protected function isMultilineString($data)
    {
        if (!is_string($data)) {
            return false;
        } else {
            return strpos($data, "\n") !== false;
        }
    }
}
