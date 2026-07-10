<?php

namespace App\Domains\Report\Services;

use App\Domains\Loan\Models\Loan;
use App\Domains\Loan\Resources\LoanResource;
use App\Domains\Report\Exports\LoanReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function loanReport(Request $request, int $perPage = 15): array
    {
        return LoanResource::collection(
            $this->buildQuery($request)->paginate($perPage)
        )->response()->getData(true);
    }

    public function exportLoans(Request $request): array
    {
        $loans = $this->buildQuery($request)->latest()->get();
        $filename = 'loan-report-' . now()->format('Y-m-d-His') . '.xlsx';

        Excel::store(new LoanReportExport($loans), $filename, 'public');

        return [
            'url' => url('storage/' . $filename),
            'filename' => $filename,
        ];
    }

    public function exportLoansPdf(Request $request): array
    {
        $loans = $this->buildQuery($request)->latest()->get();
        $filename = 'loan-report-' . now()->format('Y-m-d-His') . '.pdf';

        $pdf = Pdf::loadView('reports.loan-report-pdf', ['loans' => $loans]);
        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'url' => url('storage/' . $filename),
            'filename' => $filename,
        ];
    }

    protected function buildQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        $query = Loan::with('borrower');

        if ($user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('borrower_id')) {
            $query->where('borrower_id', $request->borrower_id);
        }

        if ($request->filled('loan_status')) {
            $query->where('status', $request->loan_status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('payments', fn ($q) => $q->where('status', $request->payment_status));
        }

        return $query;
    }
}
