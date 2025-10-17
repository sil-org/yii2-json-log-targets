# Yii2 JSON Log Targets

A collection of Yii2 log targets that format the log message as a JSON string.

## Usage

### EmailServiceTarget

The EmailServiceTarget is used to send logs to the email endpoint
in [ID Broker](https://github.com/sil-org/idp-id-broker).

Note, this target can throw an exception that should be excluded in the target configuration to ensure a looping
event is avoided. The exception is `Sil\Idp\IdBroker\Client\EmailServiceClientException`.

As part of a Yii2 app configuration:

```php
    'log' => [
        'targets' => [
            [
                'class' => 'Sil\JsonLog\target\EmailServiceTarget',
                'levels' => ['error'],
                'except' => [
                    'Sil\Idp\IdBroker\Client\EmailServiceClientException',
                ],
                'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                'message' => [
                    'to' => 'alerts@mydomain.com',
                    'cc' => 'noc@mydomain.com',         // optional
                    'bcc' => 'bcc@mydomain.com',     // optional
                    'subject' => 'Alert from application',
                ],
                'baseUrl' => 'https://emailservice.mydomain.com',
                'accessToken' => 'asdf1234',
                'assertValidIp' => true,
                'validIpRanges' => ['10.0.10.0/24','127.0.0.1'],
                'enabled' => true,
            ],
        ],
    ],
```

### JsonFileTarget

The JsonFileTarget is just like the standard FileTarget except it accepts an array of data in the message
and formats it as json before writing to a file.

As part of a Yii2 app configuration:

```php
    'log' => [
        'targets' => [
            [
                'class' => 'Sil\JsonLog\target\JsonFileTarget',
                'levels' => ['error', 'warning'],
                'logVars' => [], // Disable logging of _SERVER, _POST, etc.
            ],
        ],
    ],
```

### JsonSyslogTarget

The JsonSyslogTarget is just like the standard SyslogTarget except it accepts an array of data in the message
and formats it as json before sending to Syslog.

As part of a Yii2 app configuration:

```php
    'log' => [
        'targets' => [
            [
                'class' => 'Sil\JsonLog\target\JsonSyslogTarget',
                'levels' => ['error', 'warning'],
                'except' => [
                    'yii\web\HttpException:401',
                    'yii\web\HttpException:404',
                ],
                'logVars' => [], // Disable logging of _SERVER, _POST, etc.
            ],
        ],
    ],
```

## Tips

### Have the log prefix (if used) return JSON

Example (to be placed into your Yii2 config file's
```['components']['log']['targets']``` array):

    [
        'class' => 'Sil\JsonLog\target\JsonFileTarget',
        'levels' => ['error', 'warning'],
        'except' => [
            'yii\web\HttpException:401',
            'yii\web\HttpException:404',
        ],
        'logVars' => [], // Disable logging of _SERVER, _POST, etc.
        'prefix' => function($message) use ($appEnv) {
            $prefixData = [
                'env' => $appEnv,
            ];
            if ( ! \Yii::$app->user->isGuest) {
                $prefixData['user'] = \Yii::$app->user->identity->email;
            }
            return \yii\helpers\Json::encode($prefixData);
        },
    ],

### If using syslog to send to Logentries, only send the JSON content

Make sure that the template you define for Logentries in your rsyslog.conf file
does not add other content before the ```%msg%``` data (aside from your
Logentries key). For example, do something like this...

    $template Logentries,"LOGENTRIESKEY %msg%\n"

... NOT like this...

    $template Logentries,"LOGENTRIESKEY %HOSTNAME% %syslogtag%%msg%\n"

## License

This is released under the MIT license (see LICENSE file).
