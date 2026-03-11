<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class MonologHandler extends AbstractProcessingHandler
{
    protected TlsClient $tlaClient;
    protected string $topicId;

    public function __construct(
        string $ak,
        string $sk,
        string $endpoint,
        string $topicId,
        string $region = 'cn-beijing',
        $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->topicId = $topicId;
        $this->tlaClient = new TlsClient($ak, $sk, $endpoint, $region);
    }

    /**
     * 单条日志写入
     * @param  LogRecord  $record
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function write(LogRecord $record): void
    {
        $this->tlaClient->putLogs($this->topicId, [$record->toArray()]);
    }
}
