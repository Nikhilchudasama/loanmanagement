<?php

namespace App\Domains\Report\Exports;

use App\Domains\Loan\Models\Loan;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LoanReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $loans
    ) {}

    public function collection(): Collection
    {
        return $this->loans;
    }

    public function headings(): array
    {
        return [
            'Loan Number',
            'Borrower',
            'Loan Type',
            'Amount',
            'Interest Rate',
            'Tenure (Months)',
            'Currency',
            'Status',
            'Created At',
        ];
    }

    public function map($loan): array
    {
        return [
            $loan->loan_number,
            $loan->borrower?->full_name,
            $loan->loan_type,
            (string) $loan->loan_amount,
            (string) $loan->interest_rate . '%',
            (string) $loan->loan_tenure_months,
            $loan->currency,
            $loan->status,
            $loan->created_at->format('Y-m-d'),
        ];
    }
}
