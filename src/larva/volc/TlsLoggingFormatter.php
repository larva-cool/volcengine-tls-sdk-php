<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class TlsLoggingFormatter extends NormalizerFormatter
{
    /**
     * @inheritDoc
     */
    public function format(LogRecord $record): array
    {
        $result = $record->toArray();
        $result['context'] = isset($result['context']) ? json_encode($result['context']) : '[]';
        $result['extra'] = isset($result['extra']) ? json_encode($result['extra']) : '[]';
        return $result;
    }
}
