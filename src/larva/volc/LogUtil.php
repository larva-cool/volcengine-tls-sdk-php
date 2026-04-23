<?php

/**
 * This is NOT a freeware, use is subject to license terms.
 */

namespace Larva\Volc;

use Tls\Log;
use Tls\LogContent;
use Tls\LogGroup;
use Tls\LogGroupList;
use Tls\LogTag;

/**
 *  日志工具类
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class LogUtil
{
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
