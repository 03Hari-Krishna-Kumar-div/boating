<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupService
{
    public function run(): string
    {
        $backupPath = config('brms.backup_path', storage_path('app/backups'));
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filename = 'brms-backup-' . now()->format('Y-m-d-H-i-s') . '.sql';

        // For SQLite, just copy the database file
        if (config('database.default') === 'sqlite') {
            $dbPath = database_path('brms.sqlite');
            if (file_exists($dbPath)) {
                copy($dbPath, $backupPath . DIRECTORY_SEPARATOR . $filename);
            }
        }

        // Log the backup
        $activityLogService = app(ActivityLogService::class);
        $activityLogService->log('backup_created', null, null, null,
            "Database backup created: {$filename}");

        return $filename;
    }

    public function cleanOld(int $keepDays = 30): void
    {
        $backupPath = config('brms.backup_path', storage_path('app/backups'));
        $files = glob($backupPath . DIRECTORY_SEPARATOR . 'brms-backup-*.sql');

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime < now()->subDays($keepDays)->timestamp) {
                unlink($file);
            }
        }
    }

    public function list(): array
    {
        $backupPath = config('brms.backup_path', storage_path('app/backups'));
        $files = glob($backupPath . DIRECTORY_SEPARATOR . 'brms-backup-*.sql');

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => Carbon::createFromTimestamp(filemtime($file))->format('Y-m-d H:i:s'),
            ];
        }

        return array_reverse($backups);
    }
}
