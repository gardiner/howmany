<?php

namespace OleTrenner\HowMany\Tests;

use OleTrenner\HowMany\PointInTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PointInTimeTest extends TestCase {
    public function testStartOfEndOf() {
        $p = PointInTime::fromDateTime(new DateTimeImmutable('2025-11-26 19:24:06'));
        $this->assertEquals($p->format('Y-m-d'), '2025-11-26');

        $this->assertEquals($p->endOfYear()->format('Y-m-d'), '2025-12-31');
        $this->assertEquals($p->startOfYear()->format('Y-m-d'), '2025-01-01');
        $this->assertEquals($p->endOfMonth()->format('Y-m-d'), '2025-11-30');
        $this->assertEquals($p->startOfMonth()->format('Y-m-d'), '2025-11-01');
        $this->assertEquals($p->endOfDay()->format('Y-m-d H:i:s'), '2025-11-26 23:59:59');
        $this->assertEquals($p->startOfDay()->format('Y-m-d H:i:s'), '2025-11-26 00:00:00');
    }

    public function testAdd() {
        $p = PointInTime::fromDateTime(new DateTimeImmutable('2024-11-12 19:24:06'));
        $this->assertEquals($p->format('Y-m-d'), '2024-11-12');
        $this->assertEquals($p->addMonth()->format('Y-m-d'), '2024-12-12');
        $this->assertEquals($p->addMonth()->addMonth()->format('Y-m-d'), '2025-01-12');

        $p = PointInTime::fromDateTime(new DateTimeImmutable('2024-02-28 19:24:06'));
        $this->assertEquals($p->addDay()->format('Y-m-d'), '2024-02-29');
        $this->assertEquals($p->addDay()->addDay()->format('Y-m-d'), '2024-03-01');
    }
}