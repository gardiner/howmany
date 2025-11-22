<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementHelper;
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

    public function getValue(int $start, int $end, ?string $filterValue): mixed
    {
        list($where, $params) = MeasurementHelper::createWhere($start, $end, $filterValue, 'l');
        $result = $this->db->load_all_extended(
            'l.url, count(l.id) num',
            Store::LOGTABLENAME . ' l',
            $where . ' GROUP BY l.url ORDER BY num DESC LIMIT 100',
            $params
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