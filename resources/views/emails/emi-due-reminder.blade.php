@component('mail::message')
# EMI Due Reminder

Dear {{ $borrower->full_name }},

This is a reminder that your EMI is due.

**EMI Details:**
- **Loan Number:** {{ $loan->loan_number }}
- **Due Date:** {{ $dueDate }}
- **Amount:** {{ $amount }}

@component('mail::button', ['url' => $signedUrl])
Pay Now
@endcomponent

Please ensure timely payment to avoid late fees.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
