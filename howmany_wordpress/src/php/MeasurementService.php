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
                'key' => 'hourly',
                'title' => 'stündlich',
                'resolution' => Resolution::Hour,
            ],
            [
                'key' => 'daily',
                'title' => 'täglich',
                'resolution' => Resolution::Day,
                'is_default' => true,
            ],
            [
                'key' => 'monthly',
                'title' => 'monatlich',
                'resolution' => Resolution::Month,
            ],
            [
                'key' => 'yearly',
                'title' => 'jährlich',
                'resolution' => Resolution::Year,
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

        if ($measurement->getType() == MeasurementType::TimeSeries) {
            list($start, $end) = $this->getTimeseriesBoundaries($timeScale, $page);
            $values = $this->applyTimeseries($measurementKey, $measurement, $start, $end, $timeScale['resolution'],
                $refresh);
        } else {
            list($start, $end) = $this->getDiscreteBoundaries($timeScale, $page);
            $values = $this->applyDiscrete($measurementKey, $measurement, $start, $end, $refresh);
        }
        return [
            'timespan' => $start->format('j.m.Y, G:i') . ' Uhr - ' . $end->format('j.m.Y, G:i') . ' Uhr',
            'values' => $values,
        ];
    }

    protected function applyDiscrete(string $key, Measurement $measurement, CarbonImmutable $start, CarbonImmutable $end, bool $refresh): array
    {
        $slot = [
            'start' => $start->timestamp,
            'end' => $end->timestamp,
            'id' => $start->format('Y-m-d-H-i') . '|' . $end->format('Y-m-d-H-i'),
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

            if (static::USE_CACHE && !$slot['is_current'] && !$slot['is_future'] && !$refresh) {
                $value = $this->store->getValue($key, $slot['id']);
            }

            if (is_null($value) && !$slot['is_future']) {
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
            if ($resolution == Resolution::Hour) {
                $slots[] = [
                    'start' => $current->timestamp,
                    'end' => $current->endOfHour()->timestamp,
                    'id' => $current->format('Y-m-d-H'),
                    'label' => $current->format('j.m., G') . ':00 Uhr',
                    'is_current' => $current->endOfHour()->isFuture(),
                    'is_future' => $current->isFuture(),
                ];
                $current = $current->addHour();
            } elseif ($resolution == Resolution::Day) {
                $slots[] = [
                    'start' => $current->startOfDay()->timestamp,
                    'end' => $current->endOfDay()->timestamp,
                    'id' => $current->format('Y-m-d'),
                    'label' => $current->format('j.m.Y'),
                    'is_current' => $current->endOfDay()->isToday(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addDay();
            } elseif ($resolution == Resolution::Month) {
                $slots[] = [
                    'start' => $current->startOfMonth()->timestamp,
                    'end' => $current->endOfMonth()->timestamp,
                    'id' => $current->format('Y-m'),
                    'label' => $current->format('M Y'),
                    'is_current' => $current->endOfMonth()->isCurrentMonth(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addMonth();
            } elseif ($resolution == Resolution::Year) {
                $slots[] = [
                    'start' => $current->startOfYear()->timestamp,
                    'end' => $current->endOfYear()->timestamp,
                    'id' => $current->format('Y'),
                    'label' => $current->format('Y'),
                    'is_current' => $current->endOfYear()->isCurrentYear(),
                    'is_future' => $current->startOfDay()->isFuture(),
                ];
                $current = $current->addYear();
            }
        }
        return $slots;
    }

    protected function getTimeseriesBoundaries(array $timeScale, int $page): array
    {
        $start = $end = $today = CarbonImmutable::now()->locale('de');

        $resolution = $timeScale['resolution']; /** @var Resolution $resolution */
        switch ($resolution) {
            case Resolution::Year:
                $yearsPerPage = 10;
                $end = $today->endOfYear()->subYears($page * $yearsPerPage);
                $start = $end->startOfYear()->subYears($yearsPerPage - 1);
                break;
            case Resolution::Month:
                $yearsPerPage = 2;
                $end = $today->endOfYear()->subYears($page * $yearsPerPage);
                $start = $end->startOfYear()->subYears($yearsPerPage - 1);
                break;
            case Resolution::Day:
                $monthsPerPage = 2;
                $end = $today->endOfMonth()->subMonths($page * $monthsPerPage);
                $start = $end->startOfMonth()->subMonths($monthsPerPage - 1);
                break;
            case Resolution::Hour:
                $daysPerPage = 3;
                $end = $today->endOfDay()->subDays($page * $daysPerPage);
                $start = $end->startOfDay()->subDays($daysPerPage - 1);
                break;
        }
        return [$start, $end];
    }

    protected function getDiscreteBoundaries(array $timeScale, int $page): array
    {
        $start = $end = $today = CarbonImmutable::now()->locale('de');

        $resolution = $timeScale['resolution']; /** @var Resolution $resolution */
        switch ($resolution) {
            case Resolution::Year:
                $start = $end->startOfYear()->subYears($page);
                $end = $start->endOfYear();
                break;
            case Resolution::Month:
                $start = $end->startOfMonth()->subMonths($page);
                $end = $start->endOfMonth();
                break;
            case Resolution::Day:
                $start = $end->startOfDay()->subDays($page);
                $end = $start->endOfDay();
                break;
            case Resolution::Hour:
                $start = $end->startOfHour()->subHours($page);
                $end = $start->endOfHour();
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