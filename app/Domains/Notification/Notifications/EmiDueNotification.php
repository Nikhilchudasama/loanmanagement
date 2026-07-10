<?php

declare(strict_types=1);

namespace App\Domains\Notification\Notifications;

use App\Domains\Loan\Models\EmiSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EmiDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public EmiSchedule $emiSchedule
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->emiSchedule->loan;
        $name = $notifiable->name ?? optional($loan->borrower)->full_name ?? 'Valued Customer';
        $dueDate = $this->emiSchedule->due_date ? date('d M Y', strtotime((string) $this->emiSchedule->due_date)) : '';

        return (new MailMessage)
            ->subject('EMI Due Reminder - ' . $loan->loan_number)
            ->greeting('Hello ' . $name . '!')
            ->line('This is a reminder that your EMI is due.')
            ->line('Loan Number: ' . $loan->loan_number)
            ->line('EMI Number: ' . $this->emiSchedule->emi_number)
            ->line('Due Date: ' . $dueDate)
            ->line('Amount: ' . number_format((float) $this->emiSchedule->total_amount, 2))
            ->action('Pay Now', URL::temporarySignedRoute('payments.secure-pay', now()->addDays(7), ['emiSchedule' => $this->emiSchedule->id]))
            ->line('Please ensure timely payment to avoid late fees.');
    }

    public function toArray(object $notifiable): array
    {
        $dueDate = $this->emiSchedule->due_date ? date('Y-m-d', strtotime((string) $this->emiSchedule->due_date)) : '';

        return [
            'event' => 'emi_due',
            'emi_schedule_id' => $this->emiSchedule->id,
            'loan_id' => $this->emiSchedule->loan_id,
            'emi_number' => $this->emiSchedule->emi_number,
            'due_date' => $dueDate,
            'amount' => (float) $this->emiSchedule->total_amount,
            'message' => 'EMI #' . $this->emiSchedule->emi_number . ' of ' . number_format((float) $this->emiSchedule->total_amount, 2) . ' is due on ' . $dueDate . '.',
        ];
    }
}
