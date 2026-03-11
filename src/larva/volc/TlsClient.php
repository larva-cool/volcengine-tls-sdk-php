<?php

namespace Larva\Volc;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
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
    /** @var Client  */
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
     * 批量写入日志
     * @param  string  $topicId
     * @param  array  $logs
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function putLogs(string $topicId, array $logs): ResponseInterface
    {
        $logs = self::buildLogs($logs);
        $binaryData = $logs->serializeToString();
        return $this->client->post('/PutLogs', [
            'headers' => [
                'Content-Type' => 'application/x-protobuf',
                'x-tls-bodyrawsize' => strlen($binaryData),
            ],
            'query' => [
                'TopicId' => $topicId,
            ],
            'body' => $binaryData,
        ]);
    }

    /**
     * 写入单条日志
     * @param  string  $topicId
     * @param  array  $log
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function putLog(string $topicId, array $log): ResponseInterface
    {
        $logs = self::buildLogs([$log]);
        $binaryData = $logs->serializeToString();
        return $this->client->post('/PutLogs', [
            'headers' => [
                'Content-Type' => 'application/x-protobuf',
                'x-tls-bodyrawsize' => strlen($binaryData),
            ],
            'query' => [
                'TopicId' => $topicId,
            ],
            'body' => $binaryData,
        ]);
    }

    /**
     * 发送 POST Json 请求
     * @param  string  $uri
     * @param  array  $params
     * @param  array  $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function postJson(string $uri, array $params, array $headers = []): ResponseInterface
    {
        return $this->client->post($uri, ['headers' => $headers, 'json' => $params]);
    }

    /**
     * 发送 POST 请求
     * @param  string  $uri
     * @param  array  $params
     * @param  array  $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post(string $uri, array $params, array $headers = []): ResponseInterface
    {
        return $this->client->post($uri, ['headers' => $headers, 'form_params' => $params]);
    }

    /**
     * 执行 Get 请求
     * @param  string  $uri
     * @param  array  $query
     * @param  array  $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function get(string $uri, array $query, array $headers = []): ResponseInterface
    {
        return $this->client->get($uri, [
            'headers' => $headers,
            'query' => $query,
        ]);
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
     * @return LogGroupList
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
    protected static function buildLogContent(array $contents): array
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
    protected static function buildLogTags(array $tags): array
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
