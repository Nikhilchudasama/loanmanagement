<?php

declare(strict_types=1);

namespace App\Domains\Notification\Notifications;

use App\Domains\Loan\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Loan $loan
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->name ?? optional($this->loan->borrower)->full_name ?? 'Valued Customer';

        return (new MailMessage)
            ->subject('Loan Approved - ' . $this->loan->loan_number)
            ->greeting('Hello ' . $name . '!')
            ->line('Your loan has been approved.')
            ->line('Loan Number: ' . $this->loan->loan_number)
            ->line('Amount: ' . number_format((float) $this->loan->loan_amount, 2))
            ->line('Interest Rate: ' . (float) $this->loan->interest_rate . '%')
            ->line('Tenure: ' . $this->loan->loan_tenure_months . ' months')
            ->action('View Loan', url('/api/loans/' . $this->loan->id))
            ->line('Thank you for choosing our services!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'loan_approved',
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'message' => 'Your loan ' . $this->loan->loan_number . ' has been approved.',
        ];
    }
}
