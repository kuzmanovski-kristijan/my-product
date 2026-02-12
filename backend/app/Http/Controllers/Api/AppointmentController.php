<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Returns available slots for a date (local time).
     * Query: date=YYYY-MM-DD&store_id=1
     */
    public function slots(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->where('is_active', true)->findOrFail($validated['store_id']);

        // MVP working hours: 10:00 - 18:00, slot 60 minutes
        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $start = $date->setTime(10, 0);
        $end = $date->setTime(18, 0);

        $slotMinutes = 60;

        $existing = Appointment::query()
            ->where('store_id', $store->id)
            ->where('status', 'booked')
            ->whereBetween('starts_at', [$start, $end])
            ->get(['starts_at', 'ends_at']);

        $taken = $existing->map(fn ($a) => CarbonImmutable::parse($a->starts_at)->format('H:i'))->flip();

        $slots = [];
        for ($t = $start; $t < $end; $t = $t->addMinutes($slotMinutes)) {
            $label = $t->format('H:i');
            $slots[] = [
                'time' => $label,
                'starts_at' => $t->toIso8601String(),
                'available' => ! isset($taken[$label]),
            ];
        }

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'city' => $store->city,
                'address' => $store->address,
            ],
            'date' => $validated['date'],
            'slot_minutes' => $slotMinutes,
            'data' => $slots,
        ]);
    }

    /**
     * Book appointment.
     * Body: store_id, starts_at (ISO), full_name, phone, email?, note?
     */
    public function book(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'starts_at' => ['required', 'date'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $store = Store::query()->where('is_active', true)->findOrFail($validated['store_id']);

        $starts = CarbonImmutable::parse($validated['starts_at'])->second(0);
        $ends = $starts->addHour();

        // Working hours check (MVP 10-18)
        $dayStart = $starts->startOfDay()->setTime(10, 0);
        $dayEnd = $starts->startOfDay()->setTime(18, 0);
        if ($starts < $dayStart || $ends > $dayEnd) {
            return response()->json(['message' => 'Невалиден термин.'], 422);
        }

        // Prevent double booking
        $exists = Appointment::query()
            ->where('store_id', $store->id)
            ->where('status', 'booked')
            ->where('starts_at', $starts)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Терминот е веќе зафатен.'], 422);
        }

        try {
            $appointment = Appointment::query()->create([
                'store_id' => $store->id,
                'user_id' => $request->user()?->id,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'note' => $validated['note'] ?? null,
                'status' => 'booked',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Терминот е веќе зафатен.'], 422);
        }

        \App\Jobs\SendAppointmentBookedEmailJob::dispatch($appointment->id);
        if ($appointment->user_id) {
            \App\Jobs\SendPushToUserJob::dispatch(
                $appointment->user_id,
                'Закажан термин',
                "Термин: {$appointment->starts_at->format('Y-m-d H:i')}",
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json(['data' => $appointment], 201);
    }
}
