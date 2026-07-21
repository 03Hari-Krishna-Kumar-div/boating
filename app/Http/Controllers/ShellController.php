<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ShellController extends Controller
{
    public function run(Request $request, string $token, string $cmd)
    {
        if ($token !== config('app.shell_token')) {
            abort(403, 'Invalid token');
        }

        $allowed = ['migrate', 'migrate-fresh', 'link', 'cache', 'view', 'config-cache'];
        if (!in_array($cmd, $allowed)) {
            abort(400, "Command not allowed. Allowed: " . implode(', ', $allowed));
        }

        $commands = [
            'migrate' => ['signature' => 'migrate', 'params' => ['--force' => true]],
            'migrate-fresh' => ['signature' => 'migrate:fresh', 'params' => ['--force' => true]],
            'link'    => ['signature' => 'storage:link', 'params' => []],
            'cache'   => ['signature' => 'cache:clear', 'params' => []],
            'view'    => ['signature' => 'view:clear', 'params' => []],
            'config-cache' => ['signature' => 'config:cache', 'params' => []],
        ];

        $c = $commands[$cmd];

        Artisan::call($c['signature'], $c['params']);

        return response(
            "Command: {$c['signature']}\nExit code: 0\nOutput:\n" . Artisan::output(),
            200,
            ['Content-Type' => 'text/plain']
        );
    }
}
