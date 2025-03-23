<?php

namespace OleTrenner\HowMany;

enum MeasurementType: string
{
    case TimeSeries = 'timeseries';
    case Discrete = 'discrete';
    case Relative = 'relative';
    case List = 'list';
}