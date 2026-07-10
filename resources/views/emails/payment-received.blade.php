@component('mail::message')
# Payment Received

Dear {{ $borrower->full_name }},

A payment has been received successfully.

**Payment Details:**
- **Transaction ID:** {{ $payment->transaction_id }}
- **Amount:** {{ $amount }}
- **Status:** {{ $payment->status }}

@component('mail::button', ['url' => url('/api/payments/' . $payment->id)])
View Payment
@endcomponent

Thank you for your payment!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
