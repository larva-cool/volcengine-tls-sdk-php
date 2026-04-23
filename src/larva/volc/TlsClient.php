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
    /** @var Client */
    protected Client $client;
    protected string $region;

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
        $this->client = new Client(['base_uri' => $endpoint, 'handler' => $stack,]);
        $this->region = $region;
    }

    /**
     * 创建一个日志项目
     *
     * @param  string  $projectName
     * @param  string  $region
     * @param  string  $desc
     * @param  string  $iamProjectName
     * @param  array  $tags
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function createProject(string $projectName, string $region = '', string $desc = '', string $iamProjectName = '', array $tags = []): ResponseInterface
    {
        $body = [
            'ProjectName' => $projectName,
            'Region' => $region ?: $this->region,
            'Description' => $desc,
        ];
        if ($iamProjectName) {
            $body['IamProjectName'] = $iamProjectName;
        }
        if ($tags) {
            $body['Tags'] = $tags;
        }

        return $this->client->post('/CreateProject', [
            'json' => $body,
        ]);
    }

    /**
     * 删除项目
     * @param  string  $projectId
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function deleteProject(string $projectId): ResponseInterface
    {
        return $this->client->DELETE('/DeleteProject', [
            'json' => [
                'ProjectId' => $projectId,
            ],
        ]);
    }

    /**
     * 修改项目
     *
     * @param  string  $projectId
     * @param  string  $projectName
     * @param  string  $desc
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function modifyProject(string $projectId, string $projectName, string $desc = ''): ResponseInterface
    {
        return $this->client->put('/ModifyProject', [
            'json' => [
                'ProjectId' => $projectId,
                'ProjectName' => $projectName,
                'Description' => $desc,
            ],
        ]);
    }

    /**
     * 查询日志项目信息
     *
     * @param  string  $projectId
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeProject(string $projectId): ResponseInterface
    {
        return $this->client->get('/DescribeProject', [
            'query' => [
                'ProjectId' => $projectId,
            ],
        ]);
    }

    /**
     * 查看当前地域下所有日志项目信息
     * @param  array  $query
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeProjects(array $query): ResponseInterface
    {
        return $this->client->get('/DescribeProjects', [
            'query' => $query,
        ]);
    }

    /**
     * 获取日志主题的分区列表
     * @param  string  $topicId  日志主题 ID。
     * @param  int  $pageNumber  分页查询时的页码。默认为 1，即从第一页数据开始返回。
     * @param  int  $pageSize  分页大小。默认为 20，最大为 100。
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeShards(string $topicId, int $pageNumber = 1, int $pageSize = 10): ResponseInterface
    {
        return $this->client->get('/DescribeShards', [
            'query' => [
                'TopicId' => $topicId,
                'PageNumber' => $pageNumber,
                'PageSize' => $pageSize
            ],
        ]);
    }

    /**
     * 手动分裂指定分区
     * @param  string  $topicId  日志主题 ID。
     * @param  int  $shardId  待手动分裂的日志分区 ID。
     * @param  int  $number  分区的分裂数量。分裂数量应为非零偶数，例如 2、4、8 或 16。分裂后读写状态分区总数不能超过 256 个。
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function manualShardSplit(string $topicId, int $shardId, int $number): ResponseInterface
    {
        return $this->client->post('/CreateProject', [
            'json' => [
                'TopicId' => $topicId,
                'ShardId' => $shardId,
                'Number' => $number,
            ],
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
     * 检索日志
     * @doc https://www.volcengine.com/docs/6470/112195?lang=zh
     * @param  array  $body
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function searchLogs(array $body): ResponseInterface
    {
        return $this->client->post('/SearchLogs', [
            'json' => $body,
        ]);
    }

    /**
     * 获取日志下载游标
     * @param  string  $topicId  要获取日志游标的日志主题 ID。
     * @param  int  $shardId  对应日志分区的 ID。您可以通过 DescribeShards 接口获取指定主题的分区列表。
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeCursor(string $topicId, int $shardId): ResponseInterface
    {
        return $this->client->get('/DescribeCursor', [
            'query' => [
                'TopicId' => $topicId,
                'ShardId' => $shardId,
            ],
        ]);
    }

    /**
     * 消费日志
     * @param  string  $topicId  要消费日志的日志主题 ID。
     * @param  int  $shardId  消费的日志主题分区的 ID。您可以通过 DescribeShards 接口获取指定主题的分区列表。
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function consumeLogs(string $topicId, int $shardId): ResponseInterface
    {
        return $this->client->get('/ConsumeLogs', [
            'query' => [
                'TopicId' => $topicId,
                'ShardId' => $shardId,
            ],
        ]);
    }

    /**
     * 创建日志下载任务
     * @param  array  $body 查询参数
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function createDownloadTask(array $body): ResponseInterface
    {
        return $this->postJson('/CreateDownloadTask', $body);
    }

    /**
     * 获取指定日志主题中的日志下载任务列表
     * @param  string  $topicId
     * @param  string  $taskName
     * @param  int  $pageNumber
     * @param  int  $pageSize
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeDownloadTasks(string $topicId, string $taskName, int $pageNumber = 1, int $pageSize = 20): ResponseInterface
    {
        return $this->get('/DescribeDownloadTasks', [
            'query' => [
                'TopicId' => $topicId,
                'TaskName' => $taskName,
                'PageNumber' => $pageNumber,
                'PageSize' => $pageSize
            ]
        ]);
    }

    /**
     * 获取指定任务对应的日志下载链接
     * @param  string  $taskId
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function describeDownloadUrl(string $taskId): ResponseInterface
    {
        return $this->get('/DescribeDownloadUrl', [
            'query' => [
                'TaskId' => $taskId,
            ]
        ]);
    }

    /**
     * 取消日志下载任务
     * @param  string  $taskId
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function cancelDownloadTask(string $taskId): ResponseInterface
    {
        return $this->postJson('/CancelDownloadTask', [
            'TaskId' => $taskId,
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
}
