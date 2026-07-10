<?php

declare(strict_types=1);

namespace App\Domains\Notification\Notifications;

use App\Domains\Payment\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->name ?? optional($this->payment->borrower)->full_name ?? 'Valued Customer';

        return (new MailMessage)
            ->subject('Payment Received - ' . $this->payment->transaction_id)
            ->greeting('Hello ' . $name . '!')
            ->line('A payment has been received.')
            ->line('Transaction ID: ' . $this->payment->transaction_id)
            ->line('Amount: ' . number_format((float) $this->payment->amount, 2))
            ->line('Status: ' . $this->payment->status)
            ->action('View Payment', url('/api/payments/' . $this->payment->id))
            ->line('Thank you for your payment!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'payment_received',
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => (float) $this->payment->amount,
            'message' => 'Payment of ' . number_format((float) $this->payment->amount, 2) . ' received.',
        ];
    }
}
