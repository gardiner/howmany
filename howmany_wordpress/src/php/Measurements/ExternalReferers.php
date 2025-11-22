<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementHelper;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class ExternalReferers implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'Externe Referrer';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::Relative;
    }

    public function getValue(int $start, int $end, ?string $filterValue): mixed
    {
        global $wpdb;
        list($where, $params) = MeasurementHelper::createWhere($start, $end, $filterValue, 'l');
        $where .= ' AND l.referer != \'\' AND l.referer NOT LIKE %s';
        $params[] = [$wpdb->esc_like(home_url()) . '%'];
        $result = $this->db->load_all_extended(
            'l.referer, count(l.id) num',
            Store::LOGTABLENAME . ' l',
            $where . ' GROUP BY l.referer ORDER BY num DESC',
            $params
        );
        $total = 0;
        foreach ($result as $row) {
            $total += $row->num;
        }
        $values = [];
        foreach ($result as $row) {
            $values[] = [
                'key' => $row->referer,
                'value' => (int)$row->num,
                'rel' => 1. * (int)$row->num / $total,
            ];
        }
        return $values;
    }
}