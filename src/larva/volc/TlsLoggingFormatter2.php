<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use DateTimeInterface;
use Monolog\Formatter\JsonFormatter;

/**
 * Encodes message information into JSON in a format compatible with Cloud logging.
 *
 * @see https://cloud.google.com/logging/docs/structured-logging
 * @see https://cloud.google.com/logging/docs/reference/v2/rest/v2/LogEntry
 *
 * @author Luís Cobucci <lcobucci@gmail.com>
 */
final class TlsLoggingFormatter2 extends JsonFormatter
{
    /** {@inheritdoc} **/
    public function format(array $record): string
    {
        // Re-key level for GCP logging
        $record['severity'] = $record['level_name'];
        $record['time'] = $record['datetime']->format(DateTimeInterface::RFC3339_EXTENDED);

        // Remove keys that are not used by GCP
        unset($record['level'], $record['level_name'], $record['datetime']);

        return parent::format($record);
    }
}
