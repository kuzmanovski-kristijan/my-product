<?php

namespace App\Jobs;

use App\Mail\AppointmentBookedMail;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAppointmentBookedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(public int $appointmentId)
    {
    }

    public function handle(): void
    {
        $appointment = Appointment::query()->with(['store'])->findOrFail($this->appointmentId);

        $to = $appointment->email ?: $appointment->user?->email;

        if (! $to) {
            return;
        }

        Mail::to($to)->send(new AppointmentBookedMail($appointment));
    }
}
