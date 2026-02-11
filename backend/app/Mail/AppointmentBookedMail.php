<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentBookedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(['store']);
    }

    public function build(): self
    {
        return $this
            ->subject('Потврда за закажан термин')
            ->markdown('mail.appointments.booked', [
                'appointment' => $this->appointment,
            ]);
    }
}
