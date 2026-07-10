<?php

declare(strict_types=1);

namespace App\Domains\Loan\Listeners;

use App\Domains\Loan\Events\LoanApproved;
use App\Domains\Notification\Notifications\LoanApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class HandleLoanApproved implements ShouldQueue
{
    public function handle(LoanApproved $event): void
    {
        $loan = $event->loan;
        $loan->load('borrower');

        $borrowerEmail = $loan->borrower?->email;

        if ($borrowerEmail) {
            Notification::route('mail', $borrowerEmail)
                ->notify(new LoanApprovedNotification($loan));
        }

        $tenantUsers = \App\Domains\Auth\Models\User::where('tenant_id', $loan->tenant_id)->get();
        Notification::send($tenantUsers, new LoanApprovedNotification($loan));
    }
}
