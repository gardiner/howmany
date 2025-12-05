<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementHelper;
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

    public function getValue(int $start, int $end, ?string $filterValue): mixed
    {
        list($where, $params) = MeasurementHelper::createWhere($start, $end, $filterValue, 'l');
        $result = $this->db->load_all_extended(
            'duration, count(duration) num',
            '(SELECT l.visit, (max(l.time)-min(l.time)) duration FROM ' . Store::LOGTABLENAME . ' l WHERE ' . $where . ' GROUP BY visit) durations',
            'TRUE GROUP BY duration ORDER BY duration LIMIT 15',
            $params
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
        $secondsInDay = 60 * 60 * 24;
        $secondsInHour = 60 * 60;
        $secondsInMinute = 60;
        $days = $hours = $minutes = 0;
        if ($seconds >= $secondsInDay) {
            $days = floor($seconds / $secondsInDay);
            $seconds -= $days * $secondsInDay;
        }
        if ($seconds >= $secondsInHour) {
            $hours = floor($seconds / $secondsInHour);
            $seconds -= $hours * $secondsInHour;
        }
        if ($seconds >= $secondsInMinute) {
            $minutes = floor($seconds / $secondsInMinute);
            $seconds -= $minutes * $secondsInMinute;
        }
        $result = [];
        if ($days) {
            $result[] = $days . 'd';
        }
        if ($hours) {
            $result[] = $hours . 'h';
        }
        if ($minutes) {
            $result[] = $minutes . 'm';
        }
        if ($seconds) {
            $result[] = $seconds . 's';
        }
        return implode('', $result);
    }
}