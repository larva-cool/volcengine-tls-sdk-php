# volcengine-tls-sdk-php

火山引擎 TLS PHP SDK

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist composer require larva/volcengine-tls-sdk-php
```

or add

```
"composer require larva/volcengine-tls-sdk-php": "~1.0"
```

to the require section of your composer.json.

使用
------------

```
$logs = [
    ['id'=>1, 'conent'=>'abc'],
    ['id'=>2, 'conent'=>'abc'],
    ['id'=>3, 'conent'=>'abc']
];


$tlaClient = new \Larva\Volc\TlsClient($ak, $sk, 'https://tls-cn-beijing.volces.com', 'cn-beijing');
$response = $tlaClient->putLogs('topicid', $logs)
print_r($response->getBody()->getContents());


```

