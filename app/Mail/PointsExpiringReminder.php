<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PointsExpiringReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Account $account,
        public readonly int $points,
        public readonly Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your points are about to expire',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.points-expiring-reminder',
        );
    }
}
