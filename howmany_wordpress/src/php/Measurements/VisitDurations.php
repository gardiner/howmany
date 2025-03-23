<?php

namespace OleTrenner\HowMany\Measurements;

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class VisitDurations implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'Besuchsdauer (Zeit)';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::Discrete;
    }

    public function getValue(int $start, int $end): mixed
    {
        $result = $this->db->load_all_extended(
            'duration, count(duration) num',
            '(SELECT l.visit, (max(l.time)-min(l.time)) duration FROM ' . Store::LOGTABLENAME . ' l WHERE l.time >= %d AND l.time <= %d GROUP BY visit) durations',
            'TRUE GROUP BY duration ORDER BY duration LIMIT 15',
            [
                $start,
                $end,
            ]
        );
        $values = [];
        foreach ($result as $row) {
            $values[] = [
                'key' => $this->readableDuration($row->duration),
                'value' => (int)$row->num,
            ];
        }
        return $values;
    }

    protected function readableDuration(int $seconds): string
    {
        $d = CarbonInterval::seconds($seconds)->cascade();
        return $d->forHumans(null, true);
    }
}