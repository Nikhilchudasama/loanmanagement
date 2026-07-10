<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Auth\Models\User;
use App\Domains\Notification\Notifications\PaymentReceivedNotification;
use App\Domains\Payment\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandlePaymentReceived implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;
        $payment->load(['loan.borrower']);

        $borrowerEmail = $payment->loan?->borrower?->email;

        if ($borrowerEmail) {
            Notification::route('mail', $borrowerEmail)
                ->notify(new PaymentReceivedNotification($payment));
        }

        $tenantUsers = User::where('tenant_id', $payment->tenant_id)->get();
        Notification::send($tenantUsers, new PaymentReceivedNotification($payment));
    }
}
