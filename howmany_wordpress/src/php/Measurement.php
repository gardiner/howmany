<?php

namespace OleTrenner\HowMany;

interface Measurement
{
    public function getTitle(): string;
    public function getType(): MeasurementType;
    public function getValue(int $start, int $end, ?string $filterValue): mixed;
}