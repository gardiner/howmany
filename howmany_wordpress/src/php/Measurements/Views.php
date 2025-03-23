<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class Views implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'Aufrufe';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::TimeSeries;
    }

    public function getValue(int $start, int $end): mixed
    {
        $result = $this->db->load_all_extended('count(*) total', Store::LOGTABLENAME, 'time >= %d AND time <= %d', [
            $start,
            $end,
        ]);
        return (int)$result[0]->total;
    }
}