<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class Urls implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'URLs';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::List;
    }

    public function getValue(int $start, int $end): mixed
    {
        $result = $this->db->load_all_extended(
            'l.url, count(l.id) num',
            Store::LOGTABLENAME . ' l',
            'l.time >= %d AND l.time <= %d GROUP BY l.url ORDER BY num DESC',
            [$start, $end]
        );
        $total = 0;
        foreach ($result as $row) {
            $total += $row->num;
        }
        $values = [];
        foreach ($result as $row) {
            $values[] = [
                'key' => $row->url,
                'value' => $row->num,
                'rel' => 1. * $row->num / $total,
            ];
        }
        return $values;
    }
}