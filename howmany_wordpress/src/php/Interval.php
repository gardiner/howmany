<?php

namespace OleTrenner\HowMany;

enum Interval: string
{
    case Recent = 'recent';
    case Month = 'month';
    case Year = 'year';
    case All = 'all';
}
