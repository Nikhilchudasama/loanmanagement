<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;

class DashboardService
{
    public function metrics(): array
    {
        $user = auth()->user();

        if ($user->tenant_id) {
            $totalBorrowers = Borrower::where('tenant_id', $user->tenant_id)->count();
            $activeLoans = Loan::where('tenant_id', $user->tenant_id)->where('status', 'active')->count();
            $outstandingAmount = Loan::where('tenant_id', $user->tenant_id)
                ->whereIn('status', ['active', 'pending'])
                ->sum('loan_amount');
        } else {
            $totalBorrowers = Borrower::count();
            $activeLoans = Loan::where('status', 'active')->count();
            $outstandingAmount = Loan::whereIn('status', ['active', 'pending'])->sum('loan_amount');
        }

        $upcomingEmis = EmiSchedule::where('status', 'pending')
            ->where('due_date', '>=', now())
            ->count();

        return [
            'total_borrowers' => $totalBorrowers,
            'active_loans' => $activeLoans,
            'outstanding_amount' => (float) $outstandingAmount,
            'upcoming_emis' => $upcomingEmis,
        ];
    }
}
