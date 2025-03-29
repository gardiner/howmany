<?php

namespace OleTrenner\HowMany;

enum Resolution: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
}
