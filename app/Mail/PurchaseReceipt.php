<?php

namespace App\Mail;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Purchase $purchase) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You earned points on your purchase',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-receipt',
        );
    }
}
