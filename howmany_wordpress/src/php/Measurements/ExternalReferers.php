<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
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

    public function getValue(int $start, int $end): mixed
    {
        global $wpdb;
        $result = $this->db->load_all_extended(
            'l.referer, count(l.id) num',
            Store::LOGTABLENAME . ' l',
            'l.time >= %d AND l.time <= %d AND l.referer != \'\' AND l.referer NOT LIKE %s GROUP BY l.referer ORDER BY num DESC',
            [$start, $end, $wpdb->esc_like(home_url()) . '%']
        );
        $total = 0;
        foreach ($result as $row) {
            $total += $row->num;
        }
        $values = [];
        foreach ($result as $row) {
            $values[] = [
                'key' => $row->referer,
                'value' => $row->num,
                'rel' => 1. * $row->num / $total,
            ];
        }
        return $values;
    }
}