<?php

namespace Jabe\Impl\Calendar;

use Jabe\Impl\ProcessEngineLogger;
use Jabe\Impl\Util\{
    ClockUtil,
    EngineUtilLogger
};

class DurationHelper
{
    //private static final EngineUtilLogger LOG = ProcessEngineLogger.UTIL_LOGGER;

    private $start;

    private $end;

    private $period;

    private bool $isRepeat = false;

    private int $times = 0;

    private int $repeatOffset = 0;

    public function __construct(?string $expressions, $startDate = null)
    {
        $expression = [];
        if (!empty($expressions)) {
            $expression = explode('/', $expressions);
        }

        if (count($expression) > 3 || empty($expression)) {
            //throw LOG.cannotParseDuration(expressions);
            throw new \Exception("Can not parse duration $expressions");
        }

        if (strpos($expression[0], "R") === 0) {
            $this->isRepeat = true;
            $this->times = strlen($expression[0]) ==  1 ? PHP_INT_MAX : intval(substr($expression[0], 1));
            $expression = array_slice($expression, 1);
        }

        if ($this->isDuration($expression[0])) {
            $this->period = new \DateInterval($expression[0]);
            $this->end = count($expression) == 1 ? null : (new \DateTime($expression[1]));
        } else {
            $this->start = (new \DateTime($expression[0]));
            if ($this->isDuration($expression[1])) {
                $this->period = new \DateInterval($expression[1]);
            } else {
                $this->end = (new \DateTime($expression[1]));
                $this->period = (clone $this->start)->diff($this->end);
            }
        }
        if ($this->start === null && $this->end === null) {
            if ($startDate === null) {
                $this->start = ClockUtil::getCurrentTime();
            } else {
                if (is_string($startDate)) {
                    $this->start = new \DateTime($startDate);
                } elseif ($startDate instanceof \DateTime) {
                    $this->start = $startDate;
                }
            }
        }
    }

    public function getDateAfter($date = null): ?\DateTime
    {
        if ($this->isRepeat) {
            return $this->getDateAfterRepeat($date === null ? ClockUtil::getCurrentTime() : $date);
        }
        if ($this->end !== null) {
            return $this->end;
        }
        return (clone $this->start)->add($this->period);
    }

    public function getTimes(): int
    {
        return $this->times;
    }

    public function isRepeat(): bool
    {
        return $this->isRepeat;
    }

    private function getDateAfterRepeat($date): ?\DateTime
    {
        // use date without the current offset for due date calculation to get the
        // next due date as it would be without any modifications, later add offset
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        $dateWithoutOffset = new \DateTime();
        $dateWithoutOffset->setTimestamp($date->getTimestamp() - $this->repeatOffset);
        if ($this->start !== null) {
            $cur = clone $this->start;
            for ($i = 0; $i < $this->times && !($cur->getTimestamp() > $dateWithoutOffset->getTimestamp()); $i += 1) {
                $cur->add($this->period);
            }
            if ($cur->getTimestamp() < $dateWithoutOffset->getTimestamp()) {
                return null;
            }
            // add offset to calculated due date
            if ($this->repeatOffset === 0) {
                return $cur;
            } else {
                $dateWithOffset = new \DateTime();
                $dateWithOffset->setTimestamp($cur->getTimestamp() + $this->repeatOffset);
                return $dateWithOffset;
            }
        }

        $cur = (clone $this->end)->sub($this->period);
        $next = clone $this->end;

        for ($i = 0; $i < $this->times && ($cur->getTimestamp() > $date->getTimestamp()); $i += 1) {
            $next = clone $cur;
            $cur = $cur->sub($this->period);
        }
        return $next->getTimestamp() < $date->getTimestamp() ? null : $next;
    }

    private function isDuration(?string $time): bool
    {
        return strpos($time, "P") === 0;
    }

    public function setRepeatOffset(int $repeatOffset): void
    {
        $this->repeatOffset = $repeatOffset;
    }
}
