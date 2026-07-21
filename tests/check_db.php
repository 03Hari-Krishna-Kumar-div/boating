<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Active rentals: " . App\Models\Rental::whereIn('status', ['active'])->count() . "\n";
echo "Pending rentals: " . App\Models\Rental::whereIn('status', ['pending'])->count() . "\n";
echo "Available boats: " . App\Models\Boat::where('status', 'available')->count() . "\n";
echo "Rented boats: " . App\Models\Boat::where('status', 'rented')->count() . "\n";

// Show any non-available boats
$boats = App\Models\Boat::where('status', '!=', 'available')->get();
foreach ($boats as $b) {
    echo "  Boat #{$b->id} ({$b->name}): status={$b->status->value}\n";
}
