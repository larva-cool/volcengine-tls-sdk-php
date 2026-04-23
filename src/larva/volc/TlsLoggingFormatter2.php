<?php
/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use Monolog\Formatter\NormalizerFormatter;

/**
 * Monolog V2的支持
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
final class TlsLoggingFormatter2 extends NormalizerFormatter
{
    /**
     * 格式化单条日志为数组（核心方法）
     * @param array $record Monolog原始日志记录数组
     * @return array 格式化后的日志数组
     */
    public function format(array $record): array
    {
        if ($record['datetime'] instanceof \DateTimeInterface) {
            $record['datetime'] = $this->formatDate($record['datetime']);
        }
        // 提取并整理日志核心字段（可按需增减）
        return [
            'datetime'  => $record['datetime'], // 标准化时间格式
            'channel'   => $record['channel'],                    // 日志通道
            'severity'=> $record['level_name'],                 // 日志级别名称（如INFO/ERROR）
            'message'   => $record['message'],                    // 日志消息
            'context'   => json_encode($this->normalize($record['context'])),  // 上下文（标准化，处理对象/资源）
            'extra'     => json_encode($this->normalize($record['extra']))     // 额外数据（标准化）
        ];
    }
}
