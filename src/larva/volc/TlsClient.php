<?php

namespace Larva\Volc;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;

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
        $logs = LogUtil::buildLogs($logs);
        $binaryData = $logs->serializeToString();
        return $this->client->post('/PutLogs', [
            'headers' => [
                'Content-Type' => 'application/x-protobuf',
                'Content-MD5' => md5($binaryData),
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
        return $this->putLogs($topicId, [$log]);
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
}
