<?php

namespace OleTrenner\HowMany;

use Carbon\CarbonImmutable;
use OleTrenner\HowMany\Measurements\Views;

class MeasurementService
{
    const USE_CACHE = true;

    public function __construct(
        protected array $measurements,
        protected Store $store,
        protected Database $db,
    )
    {
    }

    public function getMeasurementDefinitions()
    {
        $result = [];
        foreach ($this->measurements as $key => $classname) {
            $measurement = new $classname($this->db);
            $result[] = [
                'key' => $key,
                'title' => $measurement->getTitle(),
                'type' => $measurement->getType(),
            ];
        }
        return $result;
    }

    public function applyMeasurement(?string $key, ?Resolution $resolution, ?array $interval, bool $refresh): array
    {
        $className = $this->measurements[$key] ?? null;
        if (!$className) {
            return [];
        }
        $measurement = new $className($this->db);
        /* @var Measurement $measurement */

        switch ($measurement->getType()) {
            case MeasurementType::TimeSeries:
                return $this->applyTimeseries($key, $measurement, $resolution, $interval, $refresh);
            case MeasurementType::Discrete:
            case MeasurementType::Relative:
            case MeasurementType::List:
                return $this->applyDiscrete($key, $measurement, $resolution, $interval, $refresh);
            default:
                return [];
        }
    }

    protected function applyDiscrete(string $key, Measurement $measurement, Resolution $resolution, ?array $interval, bool $refresh): array
    {
        $today = CarbonImmutable::now();
        $slot = [
            'start' => 0,
            'end' => $today->endOfDay()->timestamp,
            'id' => '',
            'is_current' => true,
        ];
        switch ($resolution) {
            case Resolution::Day:
                break;
        }

        $value = null;

        if (static::USE_CACHE && !$slot['is_current'] && !$refresh) {
            $value = $this->store->getValue($key, $slot['id']);
        }

        if (!$value) {
            $values = $measurement->getValue($slot['start'], $slot['end']);
            if (static::USE_CACHE && !is_null($value) && !$slot['is_current']) {
                $this->store->storeValue($key, $slot['id'], $value);
            }
        }

        return [
            'slot' => $slot['id'],
            'values' => $values,
        ];
    }

    protected function applyTimeseries(string $key, Measurement $measurement, Resolution $resolution, ?array $interval, bool $refresh): array
    {
        $slots = $this->prepareSlots($resolution, $interval);
        $result = [];
        foreach ($slots as $slot) {
            $value = null;

            if (static::USE_CACHE && !$slot['is_current'] && !$refresh) {
                $value = $this->store->getValue($key, $slot['id']);
            }

            if (!$value) {
                $value = $measurement->getValue($slot['start'], $slot['end']);
                if (static::USE_CACHE && !is_null($value) && !$slot['is_current']) {
                    $this->store->storeValue($key, $slot['id'], $value);
                }
            }

            $result[] = [
                'slot' => $slot['id'],
                'value' => $value,
            ];
        }
        return $result;
    }

    protected function prepareSlots(?Resolution $resolution, ?array $interval): array
    {
        $slots = [];

        if ($resolution == Resolution::Day) {
            $today = CarbonImmutable::now();
            $firstOfMonth = $today->startOfMonth();
            $endOfMonth = $firstOfMonth->endOfMonth();

            $current = $firstOfMonth->copy();
            while ($current < $endOfMonth && $current <= $today) {
                $slots[] = [
                    'start' => $current->startOfDay()->timestamp,
                    'end' => $current->endOfDay()->timestamp,
                    'id' => $current->format('Y-m-d'),
                    'is_current' => $current->isToday(),
                ];
                $current = $current->addDay();
            }
        } elseif ($resolution == Resolution::Month) {
            $thisMonth = CarbonImmutable::now()->endOfMonth();
            $firstOfYear = $thisMonth->startOfYear()->subYear();
            $endOfYear = $thisMonth->endOfYear();

            $current = $firstOfYear->copy();
            while ($current < $endOfYear && $current <= $thisMonth) {
                $slots[] = [
                    'start' => $current->startOfMonth()->timestamp,
                    'end' => $current->endOfMonth()->timestamp,
                    'id' => $current->format('Y-m'),
                    'is_current' => $current->isCurrentMonth(),
                ];
                $current = $current->addMonth();
            }
        } elseif ($resolution == Resolution::Year) {
            $thisYear = CarbonImmutable::now()->endOfYear();
            $current = $thisYear->subYears(10);
            while ($current <= $thisYear) {
                $slots[] = [
                    'start' => $current->startOfYear()->timestamp,
                    'end' => $current->endOfYear()->timestamp,
                    'id' => $current->format('Y'),
                    'is_current' => $current->isCurrentYear(),
                ];
                $current = $current->addYear();
            }
        }

        return $slots;
    }
}