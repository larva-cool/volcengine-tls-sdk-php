<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

/**
 * Monolog V3的支持
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class TlsLoggingFormatter extends NormalizerFormatter
{
    /**
     * {@inheritDoc}
     */
    public function format(LogRecord $record): array
    {
        $normalized = $this->normalizeRecord($record);
        if ($normalized['datetime'] instanceof \DateTimeInterface) {
            $normalized['datetime'] = $this->formatDate($normalized['datetime']);
        }
        $normalized['context'] = json_encode($this->normalize($normalized['context']));  // 上下文（标准化，处理对象/资源）
        $normalized['extra'] = json_encode($this->normalize($normalized['extra']));     // 额外数据（标准化）

        return $normalized;
    }
}
