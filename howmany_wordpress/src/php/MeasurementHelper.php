<?php

namespace OleTrenner\HowMany;

class MeasurementHelper
{
    public static function createWhere(int $start, int $end, ?string $filterValue, ?string $tableAlias=null): array
    {
        $prefix = $tableAlias ? ($tableAlias . '.') : '';
        $where = $prefix . 'time >= %d AND ' . $prefix . 'time <= %d';
        $params = [
            $start,
            $end,
        ];
        if ($filterValue) {
            $where .= ' AND url LIKE %s';
            $params[] = '%' . self::esc_like($filterValue) . '%';
        }
        return [$where, $params];
    }

    protected static function esc_like($text) {
        global $wpdb;
        return $wpdb->esc_like($text);
    }
}