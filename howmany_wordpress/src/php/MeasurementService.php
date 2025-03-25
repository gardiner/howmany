<?php

namespace OleTrenner\HowMany;

use Carbon\CarbonImmutable;
use OleTrenner\HowMany\Measurements\Views;

class MeasurementService
{
    const USE_CACHE = false;

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

        switch ($measurement->getType()) {
            case MeasurementType::TimeSeries:
                return $this->applyTimeseries($measurementKey, $measurement, $timeScale, $page, $refresh);
            case MeasurementType::Discrete:
            case MeasurementType::Relative:
            case MeasurementType::List:
                return $this->applyDiscrete($measurementKey, $measurement, $timeScale, $page, $refresh);
            default:
                return [];
        }
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

    protected function applyDiscrete(string $key, Measurement $measurement, array $timeScale, int $page, bool $refresh): array
    {
        list($start, $end) = $this->getBoundaries($timeScale, $page);
        $slot = [
            'start' => $start->timestamp,
            'end' => $end->timestamp,
            'id' => '',
            'is_current' => true,
        ];

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

    protected function applyTimeseries(string $key, Measurement $measurement, array $timeScale, int $page, bool $refresh): array
    {
        $slots = $this->prepareSlots($timeScale, $page);
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

    protected function prepareSlots(array $timeScale, int $page): array
    {
        $interval = $timeScale['interval'];
        $resolution = $timeScale['resolution'];
        list($start, $end) = $this->getBoundaries($timeScale, $page);

        $slots = [];
        $current = $start->copy();
        while ($current < $end) {
            if ($resolution == Resolution::Day) {
                $slots[] = [
                    'start' => $current->startOfDay()->timestamp,
                    'end' => $current->endOfDay()->timestamp,
                    'id' => $current->format('Y-m-d'),
                    'is_current' => $current->isToday(),
                ];
                $current = $current->addDay();
            } elseif ($resolution == Resolution::Month) {
                $slots[] = [
                    'start' => $current->startOfMonth()->timestamp,
                    'end' => $current->endOfMonth()->timestamp,
                    'id' => $current->format('Y-m'),
                    'is_current' => $current->isCurrentMonth(),
                ];
                $current = $current->addMonth();
            } elseif ($resolution == Resolution::Year) {
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

    protected function getBoundaries(array $timeScale, int $page): array
    {
        $today = CarbonImmutable::now();

        $interval = $timeScale['interval']; /** @var Interval $interval */
        switch ($interval) {
            case Interval::All:
                $start = $today->startOfYear()->subYears(10);
                $end = $today->endOfDay();
                break;
            case Interval::Year:
                $start = $today->startOfYear();
                $end = $today->endOfDay();
                break;
            case Interval::Month:
                $start = $today->startOfMonth();
                $end = $today->endOfDay();
                break;
            case Interval::Recent:
                $start = $today->startOfDay()->subDays(30);
                $end = $today->endOfDay();
                break;
        }
        return [$start, $end];
    }

}