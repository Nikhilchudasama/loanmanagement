<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loan Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        h1 { text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; font-size: 11px; color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Loan Report</h1>
    <div class="meta">Generated on {{ now()->format('F d, Y H:i:s') }}</div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Borrower</th>
                <th>Principal</th>
                <th>Interest Rate</th>
                <th>Term (Months)</th>
                <th>Status</th>
                <th>Currency</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loans as $loan)
                <tr>
                    <td>{{ $loan->id }}</td>
                    <td>{{ $loan->borrower?->full_name ?? 'N/A' }}</td>
                    <td>{{ number_format($loan->loan_amount, 2) }}</td>
                    <td>{{ $loan->interest_rate }}%</td>
                    <td>{{ $loan->loan_tenure_months }}</td>
                    <td>{{ ucfirst($loan->status) }}</td>
                    <td>{{ $loan->currency }}</td>
                    <td>{{ $loan->created_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
