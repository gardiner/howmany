<?php

namespace OleTrenner\HowMany\Measurements;

use OleTrenner\HowMany\Database;
use OleTrenner\HowMany\Measurement;
use OleTrenner\HowMany\MeasurementType;
use OleTrenner\HowMany\Store;

class UserPlatforms extends UserAgents implements Measurement
{

    public function getTitle(): string
    {
        return 'Plattform';
    }

    protected function formatUseragent(string $useragent): string
    {
        $useragent = json_decode($useragent, true);
        return !empty($useragent['platform']) ? $useragent['platform'] : 'Unbekannt';
    }
}