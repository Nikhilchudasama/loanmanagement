<?php

declare(strict_types=1);

namespace App\Domains\Notification\Commands;

use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Notification\Jobs\SendEmiReminderJob;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Console\Command;

class SendEmiReminders extends Command
{
    protected $signature = 'emi:send-reminders {--days= : Override all tenants with a fixed reminder window}';
    protected $description = 'Send EMI due reminders for upcoming payments per tenant';

    public function handle(): int
    {
        $overrideDays = $this->option('days') ? (int) $this->option('days') : null;
        $dispatched = 0;

        $tenants = Tenant::where('status', 'active')->get();

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            /** @var array<string, mixed> $notificationSettings */
            $notificationSettings = $tenant->notification_settings ?? [];
            $days = $overrideDays ?? (int) ($notificationSettings['reminder_days'] ?? 3);
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $dueEmis = EmiSchedule::with('loan.borrower')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->whereDate('due_date', $targetDate)
                ->get();

            foreach ($dueEmis as $emi) {
                SendEmiReminderJob::dispatch($emi);
                $dispatched++;
            }
        }

        if ($dispatched === 0) {
            $this->info('No EMIs due.');
        } else {
            $this->info("Dispatched {$dispatched} EMI reminder jobs.");
        }

        return self::SUCCESS;
    }
}
