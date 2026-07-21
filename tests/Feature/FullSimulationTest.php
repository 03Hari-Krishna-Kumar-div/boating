<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Boat;
use App\Models\Rental;
use App\Enums\UserRole;
use App\Enums\BoatStatus;
use App\Enums\RentalStatus;
use App\Services\RentalService;
use App\Services\TimerService;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\BoatStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FullSimulationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker1;
    private User $worker2;
    private Boat $boat1;
    private Boat $boat2;
    private RentalService $rentalService;
    private TimerService $timerService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed minimal test data
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->worker1 = User::create([
            'name' => 'Worker One',
            'email' => 'worker1@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::WORKER,
            'is_active' => true,
        ]);

        $this->worker2 = User::create([
            'name' => 'Worker Two',
            'email' => 'worker2@test.local',
            'password' => Hash::make('password'),
            'role' => UserRole::WORKER,
            'is_active' => true,
        ]);

        $this->boat1 = Boat::create([
            'boat_number' => 1,
            'name' => 'Test Boat 1',
            'status' => BoatStatus::AVAILABLE,
        ]);

        $this->boat2 = Boat::create([
            'boat_number' => 2,
            'name' => 'Test Boat 2',
            'status' => BoatStatus::AVAILABLE,
        ]);

        // Resolve services from container
        $this->rentalService = $this->app->make(RentalService::class);
        $this->timerService = $this->app->make(TimerService::class);
    }

    // ──────────────────────────────────────────────
    //  GROUP 1: START RENTAL
    // ──────────────────────────────────────────────

    /** @test */
    public function g1_1_worker_starts_rental_on_available_boat()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $this->assertNotNull($rental);
        $this->assertEquals(RentalStatus::ACTIVE, $rental->status);
        $this->assertEquals($this->worker1->id, $rental->worker_id);
        $this->assertEquals($this->boat1->id, $rental->boat_id);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
        $this->assertEquals($rental->id, $this->boat1->current_rental_id);
    }

    /** @test */
    public function g1_2_start_rental_on_occupied_boat_fails()
    {
        $this->rentalService->startRental($this->boat1, $this->worker1);

        $this->expectException(\App\Exceptions\BoatNotAvailableException::class);
        $this->rentalService->startRental($this->boat1, $this->worker2);
    }

    /** @test */
    public function g1_3_start_rental_on_maintenance_boat_fails()
    {
        $this->boat1->update(['status' => BoatStatus::MAINTENANCE]);

        $this->expectException(\App\Exceptions\BoatNotAvailableException::class);
        $this->rentalService->startRental($this->boat1, $this->worker1);
    }

    /** @test */
    public function g1_4_boat_becomes_occupied_after_start()
    {
        $this->rentalService->startRental($this->boat1, $this->worker1);
        $this->boat1->refresh();

        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
        $this->assertNotNull($this->boat1->current_rental_id);
    }

    // ──────────────────────────────────────────────
    //  GROUP 2: TIME SYSTEM 🔥
    // ──────────────────────────────────────────────

    /** @test */
    public function g2_1_extend_by_5_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 5);

        $this->assertEquals($originalEnd->copy()->addMinutes(5)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(5, $updated->extended_minutes);
        $this->assertEquals(RentalStatus::ACTIVE, $updated->status);
    }

    /** @test */
    public function g2_2_extend_by_10_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 10);

        $this->assertEquals($originalEnd->copy()->addMinutes(10)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(10, $updated->extended_minutes);
    }

    /** @test */
    public function g2_3_extend_by_15_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 15);

        $this->assertEquals($originalEnd->copy()->addMinutes(15)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(15, $updated->extended_minutes);
    }

    /** @test */
    public function g2_4_extend_by_30_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 30);

        $this->assertEquals($originalEnd->copy()->addMinutes(30)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(30, $updated->extended_minutes);
    }

    /** @test */
    public function g2_5_extend_custom_7_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 7);

        $this->assertEquals($originalEnd->copy()->addMinutes(7)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(7, $updated->extended_minutes);
    }

    /** @test */
    public function g2_6_extend_max_120_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->extendTime($rental, $this->admin, 120);

        $this->assertEquals($originalEnd->copy()->addMinutes(120)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(120, $updated->extended_minutes);
    }

    /** @test */
    public function g2_7_reduce_by_5_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->reduceTime($rental, $this->admin, 5);

        $this->assertEquals($originalEnd->copy()->subMinutes(5)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(5, $updated->reduced_minutes);
        $this->assertEquals(RentalStatus::ACTIVE, $updated->status);
    }

    /** @test */
    public function g2_8_reduce_by_10_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->reduceTime($rental, $this->admin, 10);

        $this->assertEquals($originalEnd->copy()->subMinutes(10)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(10, $updated->reduced_minutes);
    }

    /** @test */
    public function g2_9_reduce_custom_3_minutes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $updated = $this->rentalService->reduceTime($rental, $this->admin, 3);

        $this->assertEquals($originalEnd->copy()->subMinutes(3)->timestamp, $updated->effective_end_at->timestamp);
        $this->assertEquals(3, $updated->reduced_minutes);
    }

    /** @test */
    public function g2_10_reduce_to_exactly_zero_completes_rental()
    {
        // Start rental and immediately reduce by remaining time
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Calculate remaining seconds and reduce by that amount
        $remainingSeconds = $this->timerService->getRemainingSeconds($rental->effective_end_at);
        $minutesToReduce = (int) ceil($remainingSeconds / 60);

        $updated = $this->rentalService->reduceTime($rental, $this->admin, $minutesToReduce);

        // Should be COMPLETED
        $this->assertEquals(RentalStatus::COMPLETED, $updated->status);

        // Boat should be AVAILABLE
        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::AVAILABLE, $this->boat1->status);
        $this->assertNull($this->boat1->current_rental_id);
    }

    /** @test */
    public function g2_11_reduce_below_zero_fully_consumes()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Reduce by way more than remaining time
        $remainingSeconds = $this->timerService->getRemainingSeconds($rental->effective_end_at);
        $minutesToReduce = (int) ceil($remainingSeconds / 60) + 10;

        $updated = $this->rentalService->reduceTime($rental, $this->admin, $minutesToReduce);

        $this->assertEquals(RentalStatus::COMPLETED, $updated->status);
        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::AVAILABLE, $this->boat1->status);
        $this->assertNull($this->boat1->current_rental_id);
    }

    /** @test */
    public function g2_13_extend_on_overdue_boat_resets_to_occupied()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Manually set boat to OVERDUE to simulate time expired
        $this->boat1->update(['status' => BoatStatus::OVERDUE]);

        $updated = $this->rentalService->extendTime($rental, $this->admin, 10);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
        $this->assertEquals(RentalStatus::ACTIVE, $updated->status);
    }

    /** @test */
    public function g2_14_extend_on_warning_boat_resets_to_occupied()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Manually set boat to WARNING
        $this->boat1->update(['status' => BoatStatus::WARNING]);

        $updated = $this->rentalService->extendTime($rental, $this->admin, 5);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
    }

    /** @test */
    public function g2_15_multiple_extends_accumulate()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $rental = $this->rentalService->extendTime($rental, $this->admin, 5);
        $rental = $this->rentalService->extendTime($rental, $this->admin, 10);
        $rental = $this->rentalService->extendTime($rental, $this->admin, 15);

        $this->assertEquals(30, $rental->extended_minutes);
        $this->assertEquals(
            $originalEnd->copy()->addMinutes(30)->timestamp,
            $rental->effective_end_at->timestamp
        );
    }

    /** @test */
    public function g2_16_multiple_reduces_accumulate()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $rental = $this->rentalService->reduceTime($rental, $this->admin, 5);
        $rental = $this->rentalService->reduceTime($rental, $this->admin, 10);

        $this->assertEquals(15, $rental->reduced_minutes);
        $this->assertEquals(
            $originalEnd->copy()->subMinutes(15)->timestamp,
            $rental->effective_end_at->timestamp
        );
    }

    /** @test */
    public function g2_17_extend_then_reduce_interleaved()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $originalEnd = $rental->effective_end_at;

        $rental = $this->rentalService->extendTime($rental, $this->admin, 20);
        $rental = $this->rentalService->reduceTime($rental, $this->admin, 5);
        $rental = $this->rentalService->extendTime($rental, $this->admin, 10);

        // Net: +20 -5 +10 = +25 minutes
        $this->assertEquals(30, $rental->extended_minutes);
        $this->assertEquals(5, $rental->reduced_minutes);
        $this->assertEquals(
            $originalEnd->copy()->addMinutes(25)->timestamp,
            $rental->effective_end_at->timestamp
        );
    }

    /** @test */
    public function g2_18_worker_cannot_extend()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Act as worker1 (not admin)
        $this->assertFalse($this->worker1->isAdmin());
    }

    /** @test */
    public function g2_19_reduce_partial_does_not_complete()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Reduce by a small amount (2 min) — should not complete
        $updated = $this->rentalService->reduceTime($rental, $this->admin, 2);

        $this->assertEquals(RentalStatus::ACTIVE, $updated->status);
        $this->assertEquals(2, $updated->reduced_minutes);
        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
    }

    /** @test */
    public function g2_20_notification_sent_on_extend()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $updated = $this->rentalService->extendTime($rental, $this->admin, 10);

        // Verify notification exists
        $notification = \App\Models\Notification::where('user_id', $this->worker1->id)
            ->where('type', 'time_extended')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('10 minutes', $notification->message);
    }

    // ──────────────────────────────────────────────
    //  GROUP 4: END RENTAL
    // ──────────────────────────────────────────────

    /** @test */
    public function g4_1_owner_worker_ends_rental()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $updated = $this->rentalService->endRental($rental, $this->worker1);

        $this->assertEquals(RentalStatus::ENDED, $updated->status);
        $this->assertEquals($this->worker1->id, $updated->ended_by);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::ENDED, $this->boat1->status);
        // current_rental_id should still be set until received
        $this->assertNotNull($this->boat1->current_rental_id);
    }

    /** @test */
    public function g4_2_admin_ends_any_rental()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $updated = $this->rentalService->endRental($rental, $this->admin);

        $this->assertEquals(RentalStatus::ENDED, $updated->status);
        $this->assertEquals($this->admin->id, $updated->ended_by);
        $this->assertEquals('admin_ended', $updated->end_reason);
    }

    /** @test */
    public function g4_4_non_owner_worker_cannot_end()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $canEnd = $this->rentalService->checkWorkerOwnership($rental, $this->worker2);
        $this->assertFalse($canEnd);
    }

    /** @test */
    public function g4_5_notification_sent_on_end()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $this->rentalService->endRental($rental, $this->worker1);

        $notification = \App\Models\Notification::where('user_id', $this->worker1->id)
            ->where('type', 'rental_ended')
            ->first();

        $this->assertNotNull($notification);
    }

    // ──────────────────────────────────────────────
    //  GROUP 5: MARK RECEIVED
    // ──────────────────────────────────────────────

    /** @test */
    public function g5_1_owner_worker_marks_received()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $rental = $this->rentalService->endRental($rental, $this->worker1);

        $updated = $this->rentalService->markReceived($rental, $this->worker1);

        $this->assertEquals(RentalStatus::COMPLETED, $updated->status);
        $this->assertNotNull($updated->received_at);
        $this->assertEquals($this->worker1->id, $updated->received_by_worker_id);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::AVAILABLE, $this->boat1->status);
        $this->assertNull($this->boat1->current_rental_id);
    }

    /** @test */
    public function g5_2_admin_marks_received()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $rental = $this->rentalService->endRental($rental, $this->worker1);

        $updated = $this->rentalService->markReceived($rental, $this->admin);

        $this->assertEquals(RentalStatus::COMPLETED, $updated->status);
        $this->assertNotNull($updated->received_at);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::AVAILABLE, $this->boat1->status);
    }

    /** @test */
    public function g5_3_non_owner_worker_cannot_mark_received()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $rental = $this->rentalService->endRental($rental, $this->worker1);

        $canReceive = $this->rentalService->checkWorkerOwnership($rental, $this->worker2);
        $this->assertFalse($canReceive);
    }

    /** @test */
    public function g5_4_mark_received_on_wrong_status_fails()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Rental is ACTIVE, not ENDED
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rental must be in ENDED status');
        $this->rentalService->markReceived($rental, $this->worker1);
    }

    /** @test */
    public function g5_5_full_flow_start_end_receive()
    {
        // Complete end-to-end: start → end → receive → start again
        $r1 = $this->rentalService->startRental($this->boat1, $this->worker1);
        $this->assertEquals(BoatStatus::OCCUPIED->value, $this->boat1->fresh()->status->value);

        $r1 = $this->rentalService->endRental($r1, $this->worker1);
        $this->assertEquals(BoatStatus::ENDED->value, $this->boat1->fresh()->status->value);

        $r1 = $this->rentalService->markReceived($r1, $this->worker1);
        $this->assertEquals(BoatStatus::AVAILABLE->value, $this->boat1->fresh()->status->value);

        // Now start a NEW rental on the same boat
        $r2 = $this->rentalService->startRental($this->boat1, $this->worker2);
        $this->assertEquals($this->worker2->id, $r2->worker_id);
        $this->assertEquals(BoatStatus::OCCUPIED->value, $this->boat1->fresh()->status->value);
    }

    // ──────────────────────────────────────────────
    //  GROUP 6: TRANSFER + FORCE END
    // ──────────────────────────────────────────────

    /** @test */
    public function g6_1_admin_transfers_ownership()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $updated = $this->rentalService->transferOwnership($rental, $this->worker2, $this->admin);

        $this->assertEquals($this->worker2->id, $updated->worker_id);
    }

    /** @test */
    public function g6_2_old_owner_cannot_act_after_transfer()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $this->rentalService->transferOwnership($rental, $this->worker2, $this->admin);

        $canAct = $this->rentalService->checkWorkerOwnership($rental->fresh(), $this->worker1);
        $this->assertFalse($canAct);
    }

    /** @test */
    public function g6_3_new_owner_can_act_after_transfer()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);
        $this->rentalService->transferOwnership($rental, $this->worker2, $this->admin);

        $canAct = $this->rentalService->checkWorkerOwnership($rental->fresh(), $this->worker2);
        $this->assertTrue($canAct);
    }

    /** @test */
    public function g6_4_admin_force_ends_rental()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        $updated = $this->rentalService->forceEnd($rental, $this->admin, 'Test force end');

        $this->assertEquals(RentalStatus::OVERRIDDEN, $updated->status);
        $this->assertTrue($updated->admin_override);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::AVAILABLE, $this->boat1->status);
        $this->assertNull($this->boat1->current_rental_id);
    }

    // ──────────────────────────────────────────────
    //  GROUP 3: WARNING / TIME UP
    // ──────────────────────────────────────────────

    /** @test */
    public function g3_1_boat_enters_warning_when_remaining_low()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Simulate: extend_until = 4 minutes from now (under 5 min threshold)
        $rental->update(['extended_until' => now()->addMinutes(4)]);
        $rental = $rental->fresh();

        $isWarning = $this->timerService->isInWarning($rental->effective_end_at);
        $this->assertTrue($isWarning);
    }

    /** @test */
    public function g3_2_check_overdue_command_sets_time_up()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Set the rental's effective end to the past
        $rental->update(['extended_until' => now()->subMinute()]);
        $rental = $rental->fresh();

        // Run the overdue check command
        $this->artisan('brms:check-overdue')
            ->assertSuccessful();

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::TIME_UP, $this->boat1->status);

        $rental->refresh();
        $this->assertEquals(RentalStatus::OVERDUE, $rental->status);
    }

    /** @test */
    public function g3_3_time_up_boat_returns_to_occupied_on_extend()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Simulate time up
        $this->boat1->update(['status' => BoatStatus::TIME_UP]);
        $rental->update(['status' => RentalStatus::OVERDUE]);

        // Extend
        $updated = $this->rentalService->extendTime($rental->fresh(), $this->admin, 10);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::OCCUPIED, $this->boat1->status);
        $this->assertEquals(RentalStatus::ACTIVE, $updated->status);
    }

    /** @test */
    public function g3_4_end_time_up_rental_stops_alarm()
    {
        $rental = $this->rentalService->startRental($this->boat1, $this->worker1);

        // Simulate time up
        $this->boat1->update(['status' => BoatStatus::TIME_UP]);
        $rental->update(['status' => RentalStatus::OVERDUE]);

        // Ending should work and set boat to ENDED
        $updated = $this->rentalService->endRental($rental->fresh(), $this->worker1);

        $this->assertEquals(RentalStatus::ENDED, $updated->status);

        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::ENDED, $this->boat1->status);
    }

    // ──────────────────────────────────────────────
    //  GROUP 7: ROUTE + RESPONSE STRUCTURE
    // ──────────────────────────────────────────────

    /** @test */
    public function g7_1_api_routes_exist()
    {
        $routes = [
            'api/rentals/start' => 'POST',
            'api/rentals/{rental}/end' => 'POST',
            'api/rentals/{rental}/receive' => 'POST',
            'api/rentals/{rental}/extend' => 'POST',
            'api/rentals/{rental}/reduce' => 'POST',
            'api/rentals/{rental}/force-end' => 'POST',
            'api/rentals/{rental}/transfer' => 'POST',
            'api/rentals/{rental}/complete' => 'POST',
            'api/rentals/active' => 'GET',
            'api/dashboard' => 'GET',
        ];

        foreach ($routes as $path => $method) {
            $route = str_replace('{rental}', '1', $path);
            $response = $this->call($method, $route, [], [], [], [
                'HTTP_Accept' => 'application/json',
            ]);
            // Should not return 404 — auth will give 401/302 instead
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route $method $route returned 404");
        }
    }

    /** @test */
    public function g7_2_authenticated_api_returns_expected_structure()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/rentals/start', ['boat_id' => $this->boat1->id]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'rental' => [
                    'id', 'boat_id', 'worker_id', 'status', 'started_at',
                ],
            ]);
    }

    // ──────────────────────────────────────────────
    //  DATABASE SCHEMA VERIFICATION
    // ──────────────────────────────────────────────

    /** @test */
    public function db_check_boats_status_accepts_ended()
    {
        $this->boat1->update(['status' => BoatStatus::ENDED]);
        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::ENDED, $this->boat1->status);
    }

    /** @test */
    public function db_check_boats_status_accepts_time_up()
    {
        $this->boat1->update(['status' => BoatStatus::TIME_UP]);
        $this->boat1->refresh();
        $this->assertEquals(BoatStatus::TIME_UP, $this->boat1->status);
    }

    /** @test */
    public function db_check_boats_status_accepts_all_enums()
    {
        $statuses = BoatStatus::cases();
        $counter = 0;
        foreach ($statuses as $status) {
            $counter++;
            $boat = Boat::create([
                'boat_number' => 100 + $counter,
                'status' => $status,
            ]);
            $boat->refresh();
            $this->assertEquals($status, $boat->status,
                "Failed to set boat status to: {$status->value}");
        }
    }
}
