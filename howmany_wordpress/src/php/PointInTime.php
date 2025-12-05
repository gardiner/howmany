<?php

namespace OleTrenner\HowMany;

use DateTimeInterface;
use DateTimeImmutable;
use Exception;

class PointInTime
{
    public function __construct(protected DateTimeImmutable $dateTime)
    {
    }

    public static function fromDateTime(DateTimeInterface $dateTime)
    {
        return new self(DateTimeImmutable::createFromInterface($dateTime));
    }

    public static function now()
    {
        return new self(new DateTimeImmutable('now'));
    }

    public function isBetween(DateTimeInterface $start, DateTimeInterface $end): bool
    {
        if ($start > $end) {
            throw new Exception('Start time ' . $start->getTimestamp() . ' is not before end time ' . $end->getTimestamp());
        }
        return $this->dateTime >= $start &&
            $this->dateTime <= $end;
    }

    public function isToday(): bool
    {
        return $this->startOfDay() == PointInTime::now()->startOfDay();
    }

    public function isCurrentMonth(): bool
    {
        return $this->startOfMonth() == PointInTime::now()->startOfMonth();
    }

    public function isCurrentYear(): bool
    {
        return $this->startOfYear() == PointInTime::now()->startOfYear();
    }

    public function isFuture(): bool
    {
        return $this->dateTime > PointInTime::now()->dateTime;
    }

    public function getTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    public function getDateTime(): DateTimeInterface
    {
        return $this->dateTime;
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }

    public function startOfYear(): self
    {
        return PointInTime::fromDateTime(
            $this->dateTime->setDate(
                $this->dateTime->format('Y'),
                1,
                1,
            )->setTime(
                0,
                0,
            )
        );
    }

    public function endOfYear(): self
    {
        return $this->startOfYear()->addYear()->subSeconds(1);
    }

    public function addYear(): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('+1 year'));
    }

    public function subYears(int $years): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $years . ' years'));
    }

    public function startOfMonth(): self
    {
        return PointInTime::fromDateTime(
            $this->dateTime->setDate(
                $this->dateTime->format('Y'),
                $this->dateTime->format('m'),
                1,
            )->setTime(
                0,
                0,
            )
        );
    }

    public function endOfMonth(): self
    {
        return $this->startOfMonth()->addMonth()->subSeconds(1);
    }

    public function addMonth(): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('+1 month'));
    }

    public function subMonths(int $months): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $months . ' months'));
    }

    public function startOfDay(): self
    {
        return PointInTime::fromDateTime(
            $this->dateTime->setDate(
                $this->dateTime->format('Y'),
                $this->dateTime->format('m'),
                $this->dateTime->format('d'),
            )->setTime(
                0,
                0,
            )
        );
    }

    public function endOfDay(): self
    {
        return $this->startOfDay()->addDay()->subSeconds(1);
    }

    public function addDay(): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('+1 day'));
    }

    public function subDays(int $days): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $days . ' days'));
    }

    public function startOfHour(): self
    {
        return PointInTime::fromDateTime(
            $this->dateTime->setTime(
                $this->dateTime->format('H'),
                0,
            )
        );
    }

    public function endOfHour(): self
    {
        return $this->startOfHour()->addHour()->subSeconds(1);
    }

    public function addHour(): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('+1 hour'));
    }

    public function subHours(int $hours): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $hours . ' hours'));
    }

    public function subMinutes(int $minutes): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $minutes . ' minutes'));
    }

    public function subSeconds(int $seconds): self
    {
        return PointInTime::fromDateTime($this->dateTime->modify('-' . $seconds . ' seconds'));
    }
}