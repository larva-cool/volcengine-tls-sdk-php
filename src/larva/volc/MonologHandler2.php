<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;

/**
 * Monolog V2的支持
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class MonologHandler2 extends AbstractProcessingHandler
{
    protected TlsClient $tlaClient;

    protected string $topicId;

    public function __construct(string $ak, string $sk, string $endpoint, string $topicId, string $region = 'cn-beijing', $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->topicId = $topicId;
        $this->tlaClient = new TlsClient($ak, $sk, $endpoint, $region);
    }

    /**
     * 单条日志写入
     */
    protected function write(array $record): void
    {
        try {
            $this->tlaClient->putLogs($this->topicId, [$record['formatted']]);
        } catch (\Exception|GuzzleException $e) {
            error_log((string) $record['formatted']);

            return;
        }
    }
}
