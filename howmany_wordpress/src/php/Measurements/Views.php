<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;

class Views implements Measurement
{
    public function __construct(protected string $title)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): MeasurementType
    {
        return MeasurementType::TimeSeries;
    }
}