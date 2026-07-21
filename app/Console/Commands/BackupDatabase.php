<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'brms:backup';
    protected $description = 'Create a database backup';

    public function handle(BackupService $backupService): int
    {
        $this->info('Creating database backup...');

        try {
            $filename = $backupService->run();
            $this->info("Backup created: {$filename}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
