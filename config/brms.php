<?php

return [
    'rental_duration_minutes' => (int) env('BRMS_RENTAL_DURATION', 45),
    'warning_minutes' => (int) env('BRMS_WARNING_MINUTES', 5),
    'alarm_interval_seconds' => (int) env('BRMS_ALARM_INTERVAL', 1),
    'session_timeout_minutes' => (int) env('BRMS_SESSION_TIMEOUT', 120),
    'online_threshold_seconds' => (int) env('BRMS_ONLINE_THRESHOLD', 10),
    'pagination_per_page' => (int) env('BRMS_PAGINATION', 50),
    'backup_path' => env('BRMS_BACKUP_PATH', storage_path('app/backups')),
];
