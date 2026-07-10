<?php

declare(strict_types=1);

namespace App\Domains\Notification\Jobs;

use App\Domains\Auth\Models\User;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Notification\Notifications\EmiDueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendEmiReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public EmiSchedule $emiSchedule
    ) {}

    public function handle(): void
    {
        $this->emiSchedule->load('loan.borrower');

        $loan = $this->emiSchedule->loan;
        $borrowerEmail = $loan->borrower?->email;

        if ($borrowerEmail) {
            Notification::route('mail', $borrowerEmail)
                ->notify(new EmiDueNotification($this->emiSchedule));
        }

        $tenantUsers = User::where('tenant_id', $loan->tenant_id)->get();
        Notification::send($tenantUsers, new EmiDueNotification($this->emiSchedule));
    }
}
