<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CallLog;
use App\Models\Reminder;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    protected $signature = 'logs:clean';
    protected $description = 'Clean old logs and records';

    public function handle()
    {
        $this->info('Cleaning old logs...');

        // Delete call logs older than 30 days
        $callLogsDeleted = CallLog::where('created_at', '<', Carbon::now()->subDays(30))->delete();
        $this->info("Deleted {$callLogsDeleted} old call logs.");

        // Delete reminders older than 60 days
        $remindersDeleted = Reminder::where('created_at', '<', Carbon::now()->subDays(60))->delete();
        $this->info("Deleted {$remindersDeleted} old reminders.");

        return 0;
    }
}