<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WorkerController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index(Request $request)
    {
        $query = User::workers()->select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', (string) (int) $search === $search ? '=' : 'like', (string) (int) $search === $search ? (int) $search : "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $workers = $query->latest()->paginate($perPage);
        return view('admin.workers.index', compact('workers'));
    }

    public function create()
    {
        return view('admin.workers.create');
    }

    public function store(StoreWorkerRequest $request)
    {
        $worker = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => UserRole::WORKER,
            'is_active' => true,
        ]);

        $this->activityLogService->log('worker_created', auth()->user(), null, null,
            "Worker {$worker->name} created");

        return redirect()->route('admin.workers.index')
            ->with('success', 'Worker created successfully.');
    }

    public function edit(User $worker)
    {
        if ($worker->role->value !== 'worker') {
            abort(404);
        }
        return view('admin.workers.edit', compact('worker'));
    }

    public function update(UpdateWorkerRequest $request, User $worker)
    {
        if ($worker->role->value !== 'worker') {
            abort(404);
        }

        $worker->update($request->validated());

        $this->activityLogService->log('worker_updated', auth()->user(), null, null,
            "Worker {$worker->name} updated");

        return redirect()->route('admin.workers.index')
            ->with('success', 'Worker updated successfully.');
    }

    public function destroy(User $worker)
    {
        if ($worker->role->value !== 'worker') {
            abort(404);
        }

        $worker->delete();

        $this->activityLogService->log('worker_deleted', auth()->user(), null, null,
            "Worker {$worker->name} deleted");

        return redirect()->route('admin.workers.index')
            ->with('success', 'Worker deleted successfully.');
    }

    public function toggleStatus(User $worker)
    {
        if ($worker->role->value !== 'worker') {
            abort(404);
        }

        $worker->update(['is_active' => !$worker->is_active]);

        $action = $worker->is_active ? 'worker_enabled' : 'worker_disabled';
        $this->activityLogService->log($action, auth()->user(), null, null,
            "Worker {$worker->name} " . ($worker->is_active ? 'enabled' : 'disabled'));

        return redirect()->route('admin.workers.index')
            ->with('success', "Worker {$worker->name} " . ($worker->is_active ? 'enabled' : 'disabled') . ".");
    }

    public function resetPassword(Request $request, User $worker)
    {
        if ($worker->role->value !== 'worker') {
            abort(404);
        }

        $request->validate(['password' => 'required|string|min:8|confirmed']);

        $worker->update(['password' => Hash::make($request->password)]);

        $this->activityLogService->log('password_reset', auth()->user(), null, null,
            "Password reset for worker {$worker->name}");

        return redirect()->route('admin.workers.index')
            ->with('success', 'Password reset successfully.');
    }
}
