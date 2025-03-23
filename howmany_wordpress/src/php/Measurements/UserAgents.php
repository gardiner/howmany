<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class UserAgents implements Measurement
{
    public function __construct(
        protected Database $db,
    )
    {
    }

    public function getTitle(): string
    {
        return 'Browser';
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::Relative;
    }

    public function getValue(int $start, int $end): mixed
    {
        $result = $this->db->load_all_extended(
            'useragent, count(id) num',
            Store::LOGTABLENAME,
            'time >= %d AND time <= %d GROUP BY useragent ORDER BY num DESC',
            [$start, $end]
        );
        $count = $this->count($result);
        $total = array_sum($count);
        $values = [];
        foreach ($count as $key => $value) {
            $values[] = [
                'key' => $key,
                'value' => $value,
                'rel' => 1. * $value / $total,
            ];
        }
        return $values;
    }

    protected function count(array $rows): array
    {
        $count = [];
        foreach ($rows as $row) {
            $key = $this->formatUseragent($row->useragent);
            $value = (int)$row->num;
            if (!array_key_exists($key, $count)) {
                $count[$key] = $value;
            } else {
                $count[$key] += $value;
            }
        }
        return $count;
    }

    protected function formatUseragent(string $useragent): string
    {
        $useragent = json_decode($useragent, true);
        if (!$useragent) {
            return 'Unbekannt';
        }
        $platform = !empty($useragent['platform']) ? ' (' . $useragent['platform'] . ')' : '';
        $useragent = trim($useragent['browser'] . ' ' . $useragent['version'] . $platform);
        return $useragent ?: 'Unbekannt';
    }
}