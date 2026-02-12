<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_slot_cannot_be_booked_twice(): void
    {
        $store = Store::query()->create([
            'name' => 'Main Salon',
            'city' => 'Skopje',
            'is_active' => true,
        ]);

        $startsAt = now()->startOfDay()->setTime(12, 0)->toIso8601String();

        $payload = [
            'store_id' => $store->id,
            'starts_at' => $startsAt,
            'full_name' => 'Test User',
            'phone' => '+38970111222',
            'email' => 'test@example.com',
        ];

        $this->postJson('/api/appointments', $payload)
            ->assertCreated();

        $this->postJson('/api/appointments', $payload)
            ->assertStatus(422);
    }
}
