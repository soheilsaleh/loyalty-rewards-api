<?php

namespace App\Listeners;

use App\Events\PurchaseRegistered;
use App\Mail\PurchaseReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPurchaseReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Email the member a receipt for the points their purchase earned.
     */
    public function handle(PurchaseRegistered $event): void
    {
        $purchase = $event->purchase;
        $purchase->loadMissing('account.user');

        $user = $purchase->account?->user;

        if ($user === null) {
            return;
        }

        Mail::to($user->email)->send(new PurchaseReceipt($purchase));
    }
}
