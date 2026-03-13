# volcengine-tls-sdk-php

火山引擎 TLS PHP SDK

说明，实时日志导入，使用接口 putlog 不是明智的选择，应该用 kafka 协议或者 使用socket 直接写入日志，这个SDK适用于小项目或者将日志批量导入，
实时导入会频繁的建立HTTP连接，消耗会很大。当然查询分析统计用这个SDK也可以。

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

