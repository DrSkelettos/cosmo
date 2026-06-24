<?php

use App\Jobs\DailyReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| Add scheduled tasks below. The weekly inspire command is a placeholder
| that confirms the scheduler is wired correctly.
|
*/

Schedule::job(new DailyReportJob)->weekdays()->at('06:00');
Schedule::job(new DailyReportJob)->weekends()->at('07:00');
