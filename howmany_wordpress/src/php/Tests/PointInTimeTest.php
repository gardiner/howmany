<?php

namespace OleTrenner\HowMany\Tests;

require_once __DIR__ . '/../../../vendor/autoload.php';

use OleTrenner\HowMany\PointInTime;
use DateTimeImmutable;
use Exception;

function assertEquals($a, $b): void
{
    if ($a != $b) {
        throw new Exception('a not equal b');
    }
}

$p = PointInTime::fromDateTime(new DateTimeImmutable('2025-11-26 19:24:06'));
assertEquals($p->format('Y-m-d'), '2025-11-26');

assertEquals($p->endOfYear()->format('Y-m-d'), '2025-12-31');
assertEquals($p->startOfYear()->format('Y-m-d'), '2025-01-01');
assertEquals($p->endOfMonth()->format('Y-m-d'), '2025-11-30');
assertEquals($p->startOfMonth()->format('Y-m-d'), '2025-11-01');
assertEquals($p->endOfDay()->format('Y-m-d H:i:s'), '2025-11-26 23:59:59');
assertEquals($p->startOfDay()->format('Y-m-d H:i:s'), '2025-11-26 00:00:00');

$p = PointInTime::fromDateTime(new DateTimeImmutable('2024-11-12 19:24:06'));
assertEquals($p->format('Y-m-d'), '2024-11-12');
assertEquals($p->addMonth()->format('Y-m-d'), '2024-12-12');
assertEquals($p->addMonth()->addMonth()->format('Y-m-d'), '2025-01-12');

$p = PointInTime::fromDateTime(new DateTimeImmutable('2024-02-28 19:24:06'));
assertEquals($p->addDay()->format('Y-m-d'), '2024-02-29');
assertEquals($p->addDay()->addDay()->format('Y-m-d'), '2024-03-01');
