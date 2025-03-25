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

    public function getTimeScaleDefinitions()
    {
        return [
            [
                'key' => 'recent',
                'title' => 'Aktuell',
                'resolution' => Resolution::Day,
                'interval' => Interval::Recent,
            ],
            [
                'key' => 'monthly',
                'title' => 'Monat',
                'resolution' => Resolution::Day,
                'interval' => Interval::Month,
            ],
            [
                'key' => 'yearly',
                'title' => 'Jahr',
                'resolution' => Resolution::Month,
                'interval' => Interval::Year,
            ],
            [
                'key' => 'all',
                'title' => 'Insgesamt',
                'resolution' => Resolution::Year,
                'interval' => Interval::All,
            ],
        ];
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

    public function applyMeasurement(?string $measurementKey, ?string $timeScaleKey, int $page, bool $refresh): array
    {
        $className = $this->measurements[$measurementKey] ?? null;
        $timeScale = $this->getTimeScale($timeScaleKey);
        if (!$className || !$timeScale) {
            return [];
        }
        $measurement = new $className($this->db); /** @var Measurement $measurement */
        list($start, $end) = $this->getBoundaries($timeScale, $page);

        switch ($measurement->getType()) {
            case MeasurementType::TimeSeries:
                $values = $this->applyTimeseries($measurementKey, $measurement, $start, $end, $timeScale['resolution'], $refresh);
                break;
            case MeasurementType::Discrete:
            case MeasurementType::Relative:
            case MeasurementType::List:
                $values = $this->applyDiscrete($measurementKey, $measurement, $start, $end, $refresh);
                break;
            default:
                return [];
        }
        return [
            'timespan' => $start->format('j.m.Y') . ' - ' . $end->format('j.m.Y'),
            'values' => $values,
        ];
    }

    protected function applyDiscrete(string $key, Measurement $measurement, CarbonImmutable $start, CarbonImmutable $end, bool $refresh): array
    {
        $slot = [
            'start' => $start->timestamp,
            'end' => $end->timestamp,
            'id' => $start->format('Y-m-d') . '|' . $end->format('Y-m-d'),
            'is_current' => CarbonImmutable::now()->isBetween($start, $end),
        ];

        $values = null;

        if (static::USE_CACHE && !$slot['is_current'] && !$refresh) {
            $values = $this->store->getValue($key, $slot['id']);
        }

        if (!$values) {
            $values = $measurement->getValue($slot['start'], $slot['end']);
            if (static::USE_CACHE && !is_null($values) && !$slot['is_current']) {
                $this->store->storeValue($key, $slot['id'], $values);
            }
        }

        return [
            'slot' => $slot['id'],
            'values' => $values,
        ];
    }

    protected function applyTimeseries(string $key, Measurement $measurement, CarbonImmutable $start, CarbonImmutable $end, Resolution $resolution, bool $refresh): array
    {
        $slots = $this->prepareSlots($start, $end, $resolution);
        $result = [];
        foreach ($slots as $slot) {
            $value = null;

            if (static::USE_CACHE && !$slot['is_current'] && !$refresh) {
                $value = $this->store->getValue($key, $slot['id']);
            }

            if (is_null($value)) {
                $value = $measurement->getValue($slot['start'], $slot['end']);
                if (static::USE_CACHE && !is_null($value) && !$slot['is_current']) {
                    $this->store->storeValue($key, $slot['id'], $value);
                }
            }

            $result[] = [
                'slot' => $slot['id'],
                'label' => $slot['label'],
                'value' => $slot['is_future'] ? null : $value,
            ];
        }
        return $result;
    }

    protected function prepareSlots(CarbonImmutable $start, CarbonImmutable $end, Resolution $resolution): array
    {
        $slots = [];
        $current = $start->copy();
        while ($current < $end) {
            if ($resolution == Resolution::Day) {
                $slots[] = [
                    'start' => $current->startOfDay()->timestamp,
                    'end' => $current->endOfDay()->timestamp,
                    'id' => $current->format('Y-m-d'),
                    'label' => $current->format('j.m.Y'),
                    'is_current' => $current->isToday(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addDay();
            } elseif ($resolution == Resolution::Month) {
                $slots[] = [
                    'start' => $current->startOfMonth()->timestamp,
                    'end' => $current->endOfMonth()->timestamp,
                    'id' => $current->format('Y-m'),
                    'label' => $current->format('M Y'),
                    'is_current' => $current->isCurrentMonth(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addMonth();
            } elseif ($resolution == Resolution::Year) {
                $slots[] = [
                    'start' => $current->startOfYear()->timestamp,
                    'end' => $current->endOfYear()->timestamp,
                    'id' => $current->format('Y'),
                    'label' => $current->format('Y'),
                    'is_current' => $current->isCurrentYear(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addYear();
            }
        }
        return $slots;
    }

    protected function getBoundaries(array $timeScale, int $page): array
    {
        $today = CarbonImmutable::now()->locale('de');

        $interval = $timeScale['interval']; /** @var Interval $interval */
        switch ($interval) {
            case Interval::All:
                $start = $today->startOfYear()->subYears(($page + 1) * 10);
                $end = $today->endOfYear()->subYears($page * 10);
                break;
            case Interval::Year:
                $start = $today->startOfYear()->subYears($page);
                $end = $start->endOfYear();
                break;
            case Interval::Month:
                $start = $today->startOfMonth()->subMonths($page);
                $end = $start->endOfMonth();
                break;
            case Interval::Recent:
                $end = $today->endOfDay()->subDays($page * 30);
                $start = $end->startOfDay()->subDays(29);
                break;
        }
        return [$start, $end];
    }

    protected function getTimeScale(?string $key): ?array
    {
        $definitions = $this->getTimeScaleDefinitions();
        foreach($definitions as $definition) {
            if ($definition['key'] == $key) {
                return $definition;
            }
        }
        return null;
    }
}