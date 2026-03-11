<?php

namespace Larva\Volc;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Tls\Log;
use Tls\LogContent;
use Tls\LogGroup;
use Tls\LogGroupList;
use Tls\LogTag;

/**
 * This is NOT a freeware, use is subject to license terms.
 */
class TlsClient
{
    protected Client $client;

    /**
     * @param  string  $ak
     * @param  string  $sk
     * @param  string  $endpoint
     * @param  string  $region
     */
    public function __construct(string $ak, string $sk, string $endpoint, string $region = 'cn-beijing')
    {
        $stack = HandlerStack::create();
        $middleware = new VolcMiddleware($ak, $sk, $region, 'TLS');
        $stack->push($middleware);
        $this->client = new Client([
            'base_uri' => $endpoint,
            'handler' => $stack,
        ]);
    }

    /**
     * 写入日志
     * @param  string  $topicId
     * @param  array  $logs
     * @return bool
     * @throws GuzzleException
     */
    public function putLogs(string $topicId, array $logs): bool
    {
        $logs = self::buildLogs($logs);
        $binaryData = $logs->serializeToString();
        $res = $this->client->post('/PutLogs', [
            'headers' => [
                'Content-Type' => 'application/x-protobuf',
                'x-tls-bodyrawsize' => strlen($binaryData),
            ],
            'query' => [
                'TopicId' => $topicId,
            ],
            'body' => $binaryData,
        ]);
        if ($res->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 直接获取客户端操作
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * 批量构建日志
     * @param  array  $logs
     * @return array
     */
    public static function buildLogs(array $logs): LogGroupList
    {
        $items = [];
        foreach ($logs as $log) {
            $logContents = self::buildLogContent($log);
            $log = new Log();
            $log->setTime(time()); // 当前 Unix 时间戳（int64）
            $log->setContents($logContents); // 添加 LogContent 元素
            $items[] = $log;
        }
        $logGroup = new LogGroup();
        $logGroup->setLogs($items); // 添加 Log 元素
        $logGroupList = new LogGroupList();
        $logGroupList->setLogGroups([$logGroup]);

        return $logGroupList;
    }

    /**
     * 构建日志内容
     * @param  array  $contents
     * @return array
     */
    public static function buildLogContent(array $contents): array
    {
        $items = [];
        foreach ($contents as $key => $value) {
            $item = new LogContent();
            $item->setKey($key);
            $item->setValue($value);
            $items[] = $item;
        }
        return $items;
    }

    /**
     * 构建日志标签
     * @param  array  $tags
     * @return array
     */
    public static function buildLogTags(array $tags): array
    {
        $items = [];
        foreach ($tags as $key => $value) {
            $item = new LogTag();
            $item->setKey($key);
            $item->setValue($value);
            $items[] = $item;
        }
        return $items;
    }
}