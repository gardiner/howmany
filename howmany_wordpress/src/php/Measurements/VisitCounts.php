<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class VisitCounts implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'Besuchsdauer (Anzahl Aufrufe)';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::Discrete;
    }

    public function getValue(int $start, int $end): mixed
    {
        $result = $this->db->load_all_extended(
            'viewcount, count(viewcount) num',
            '(SELECT l.visit, count(l.url) viewcount FROM ' . Store::LOGTABLENAME . ' l WHERE l.time >= %d AND l.time <= %d GROUP BY l.visit) viewcounts',
            'TRUE GROUP BY viewcount ORDER BY viewcount LIMIT 15',
            [
                $start,
                $end,
            ]
        );
        $values = [];
        foreach ($result as $row) {
            $values[] = [
                'key' => $row->viewcount,
                'value' => (int)$row->num,
            ];
        }
        return $values;
    }
}