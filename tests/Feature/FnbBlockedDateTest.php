<?php

namespace Tests\Feature;

use App\Models\FnbBlockedDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FnbBlockedDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_blocked_date_timeslots()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a blocked date with some time slots
        $blockedDate = FnbBlockedDate::create([
            'blocked_date' => '2024-12-25',
            'timeslots' => ['10:00', '10:30', '11:00'],
            'reason' => 'Christmas Day',
            'client_identifier' => $user->identifier
        ]);

        // Update the blocked date to remove one time slot (unblock 10:30)
        $response = $this->putJson("/api/fnb-blocked-dates/{$blockedDate->id}", [
            'timeslots' => ['10:00', '11:00'], // Remove 10:30
            'reason' => 'Christmas Day - Updated'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Blocked date updated successfully'
            ]);

        // Verify the database was updated correctly
        $this->assertDatabaseHas('fnb_blocked_dates', [
            'id' => $blockedDate->id,
            'timeslots' => json_encode(['10:00', '11:00']),
            'reason' => 'Christmas Day - Updated'
        ]);
    }

    public function test_can_update_blocked_date_to_empty_timeslots()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a blocked date with some time slots
        $blockedDate = FnbBlockedDate::create([
            'blocked_date' => '2024-12-25',
            'timeslots' => ['10:00', '10:30'],
            'reason' => 'Christmas Day',
            'client_identifier' => $user->identifier
        ]);

        // Update the blocked date to have no time slots (effectively unblocking all)
        $response = $this->putJson("/api/fnb-blocked-dates/{$blockedDate->id}", [
            'timeslots' => [], // Empty array - all slots unblocked
            'reason' => 'Christmas Day - All slots unblocked'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Blocked date updated successfully'
            ]);

        // Verify the database was updated correctly
        $this->assertDatabaseHas('fnb_blocked_dates', [
            'id' => $blockedDate->id,
            'timeslots' => json_encode([]),
            'reason' => 'Christmas Day - All slots unblocked'
        ]);
    }

    public function test_validation_prevents_invalid_timeslots()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a blocked date
        $blockedDate = FnbBlockedDate::create([
            'blocked_date' => '2024-12-25',
            'timeslots' => ['10:00', '10:30'],
            'reason' => 'Christmas Day',
            'client_identifier' => $user->identifier
        ]);

        // Try to update with invalid time format
        $response = $this->putJson("/api/fnb-blocked-dates/{$blockedDate->id}", [
            'timeslots' => ['10:00', 'invalid-time'],
            'reason' => 'Test'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timeslots.1']);
    }
}
