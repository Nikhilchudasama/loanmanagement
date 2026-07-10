@component('mail::message')
# Loan Approved

Dear {{ $borrower->full_name }},

Your loan has been approved.

**Loan Details:**
- **Loan Number:** {{ $loan->loan_number }}
- **Amount:** {{ $amount }}
- **Interest Rate:** {{ $interestRate }}%
- **Tenure:** {{ $tenure }} months

@component('mail::button', ['url' => url('/api/loans/' . $loan->id)])
View Loan
@endcomponent

Thank you for choosing our services!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
