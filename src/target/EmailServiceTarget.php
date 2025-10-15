<?php

namespace Sil\JsonLog\target;

use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\JsonLog\JsonLogHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\log\Target;

class EmailServiceTarget extends Target
{

    /**
     * @var string $baseUrl Email Service API base url
     */
    public $baseUrl;

    /**
     * @var string $accessToken Email Service API access token
     */
    public $accessToken;

    /**
     * @var bool $assertValidIp Whether or not to assert IP address resolved for Email Service is considered valid
     */
    public $assertValidIp = true;

    /**
     * @var array $validIpRanges Array of IP ranges considered valid, e.g. ['127.0.0.1','10.0.20.1/16']
     */
    public $validIpRanges = ['127.0.0.1'];

    /**
     * @var array $message Email config, properties: to, cc, bcc, subject
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->baseUrl)) {
            throw new InvalidConfigException('The "baseUrl" option must be set for EmailServiceTarget::baseUrl.');
        }
        if (empty($this->accessToken)) {
            throw new InvalidConfigException(
                'The "accessToken" option must be set for EmailServiceTarget::accessToken.'
            );
        }
        if ($this->assertValidIp && empty($this->validIpRanges)) {
            throw new InvalidConfigException(
                'EmailServiceTarget::validIpRanges must be set when EmailServiceTarget::assertValidIp is true.'
            );
        }
        if (empty($this->message['to'])) {
            throw new InvalidConfigException('The "to" option must be set for EmailServiceTarget::message.');
        }

        $this->message['subject'] = $this->message['subject'] ?? 'System Alert from EmailService';
        $this->message['cc'] = $this->message['cc'] ?? '';
        $this->message['bcc'] = $this->message['bcc'] ?? '';
    }


    /**
     * Format a log message as a string of JSON.
     *
     * @param array $logMessageData The array of log data provided by Yii. See
     *     `\yii\log\Logger::messages`.
     * @return string The JSON-encoded log data.
     */
    public function formatMessage($logMessageData)
    {
        $jsonString = JsonLogHelper::formatAsJson(
            $logMessageData,
            $this->getMessagePrefix($logMessageData)
        );

        return Json::encode(Json::decode($jsonString), JSON_PRETTY_PRINT);
    }

    /**
     * Send message to Email Service
     */
    public function export()
    {
        $emailService = new IdBrokerClient(
            $this->baseUrl,
            $this->accessToken,
            [
                IdBrokerClient::ASSERT_VALID_IP_CONFIG => $this->assertValidIp,
                IdBrokerClient::TRUSTED_IPS_CONFIG => $this->validIpRanges,
            ]
        );

        foreach ($this->messages as $msg) {
            $body = $this->formatMessage($msg);

            $emailService->email([
                'to_address' => $this->message['to'],
                'cc_address' => $this->message['cc'],
                'bcc_address' => $this->message['bcc'],
                'subject' => $this->message['subject'],
                'text_body' => $body,
                'html_body' => sprintf("<pre>%s</pre>", $body),
            ]);
        }
    }
}
