<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(private BackupService $backupService) {}

    public function index()
    {
        $backups = $this->backupService->list();
        return view('admin.backups.index', compact('backups'));
    }

    public function run()
    {
        $filename = $this->backupService->run();

        return redirect()->route('admin.backups.index')
            ->with('success', "Backup {$filename} created successfully.");
    }
}
