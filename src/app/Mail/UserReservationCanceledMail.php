<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UserReservationCanceledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【Lash Brow Ohana】ご予約キャンセル完了のお知らせ'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-reservation-canceled',
            with: [
                'reservation' => $this->reservation,
            ]
        );
    }
}
