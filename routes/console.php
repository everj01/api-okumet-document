<?php

use App\Console\Commands\EnviarRecordatorios;
use App\Console\Commands\RespaldarSistema;
use Illuminate\Support\Facades\Schedule;

Schedule::command(EnviarRecordatorios::class)->dailyAt('07:00');
Schedule::command(RespaldarSistema::class)->dailyAt('02:00');
