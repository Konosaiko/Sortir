<?php

// src/Scheduler/UpdateSortiesStatusProvider.php

namespace App\Scheduler;

use App\Scheduler\Message\CheckSortiesStatus;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('update_sorties_status')]
class UpdateSortieStatusProvider implements ScheduleProviderInterface
{
    private Schedule $schedule;

    public function getSchedule(): Schedule
    {
        if (!isset($this->schedule)) {
            $schedule = new Schedule();
            $schedule->add(RecurringMessage::every('1 minute', new CheckSortiesStatus()));
            $this->schedule = $schedule;
        }

        return $this->schedule;
    }
}
