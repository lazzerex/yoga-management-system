<?php

namespace App\Console\Commands;

use App\Modules\Operations\ClassSession\Actions\GenerateClassSessionsAction;
use Illuminate\Console\Command;

class GenerateClassSessions extends Command
{
    protected $signature = 'sessions:generate';

    protected $description = 'Generate upcoming class_sessions rows from active class_schedules templates (8-week rolling window)';

    public function handle(GenerateClassSessionsAction $action): int
    {
        $created = $action->execute();

        $this->info("Generated {$created} class session(s).");

        return self::SUCCESS;
    }
}
