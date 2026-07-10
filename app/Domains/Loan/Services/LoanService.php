<?php

namespace App\Domains\Loan\Services;

use App\Domains\ActivityLog\Services\ActivityLogService;
use App\Domains\Loan\Events\LoanApproved;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Loan\Resources\LoanResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoanService
{
    public function __construct(
        protected FlatRateCalculator $flatRate,
        protected ReducingBalanceCalculator $reducingBalance,
        protected ActivityLogService $activityLogService
    ) {}

    public function list(int $perPage = 15): array
    {
        return LoanResource::collection(
            Loan::with('borrower')->paginate($perPage)
        )->response()->getData(true);
    }

    public function create(array $data): Loan
    {
        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            throw new HttpException(403, 'Super admins cannot create loans directly.');
        }

        $data['tenant_id'] = $tenantId;
        $data['loan_number'] = $this->generateLoanNumber();
        $data['status'] = 'pending';

        return Loan::create($data);
    }

    public function find(Loan $loan): Loan
    {
        $loan->load('borrower', 'emiSchedules');

        return $loan;
    }

    public function update(Loan $loan, array $data): Loan
    {
        $loan->update($data);

        return $loan;
    }

    public function delete(Loan $loan): void
    {
        $loan->delete();
    }

    public function generateEmiSchedule(Loan $loan): void
    {
        $calculator = $this->getCalculator($loan->interest_type);
        $schedule = $calculator->calculate(
            (float) $loan->loan_amount,
            (float) $loan->interest_rate,
            $loan->loan_tenure_months
        );

        $startDate = Carbon::parse($loan->emi_start_date);
        $rows = [];

        foreach ($schedule as $item) {
            $rows[] = [
                'tenant_id' => $loan->tenant_id,
                'loan_id' => $loan->id,
                'emi_number' => $item['emi_number'],
                'due_date' => $startDate->copy()->addMonths($item['emi_number'] - 1)->format('Y-m-d'),
                'principal_amount' => $item['principal_amount'],
                'interest_amount' => $item['interest_amount'],
                'total_amount' => $item['total_amount'],
                'outstanding_balance' => $item['outstanding_balance'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        EmiSchedule::insert($rows);
    }

    public function foreclose(Loan $loan, ?float $foreclosureCharges = null): Loan
    {
        if ($loan->status !== 'active') {
            throw new HttpException(422, 'Only active loans can be foreclosed.');
        }

        $pendingEmis = $loan->emiSchedules()->where('status', 'pending')->get();
        $outstandingPrincipal = $loan->loan_amount;
        $paidPrincipal = $loan->emiSchedules()->where('status', 'paid')->sum('principal_amount');
        $remainingPrincipal = max(0, $outstandingPrincipal - $paidPrincipal);

        $charges = $foreclosureCharges ?? ($loan->foreclosure_charges ?? ($remainingPrincipal * 0.05));
        $totalDue = $remainingPrincipal + $charges;

        return DB::transaction(function () use ($loan, $charges, $totalDue, $remainingPrincipal, $pendingEmis): Loan {
            $loan->update([
                'status' => 'closed',
                'foreclosure_charges' => $charges,
                'foreclosed_at' => now(),
                'notes' => ($loan->notes ? $loan->notes . "\n" : '') . 'Foreclosed on ' . now()->format('Y-m-d') . '. Charges: ' . $charges,
            ]);

            foreach ($pendingEmis as $emi) {
                $emi->update([
                    'status' => 'foreclosed',
                ]);
            }

            $this->activityLogService->log(
                description: 'Loan foreclosed #' . $loan->id,
                event: 'foreclosed',
                subjectType: Loan::class,
                subjectId: $loan->id,
                properties: [
                    'loan_number' => $loan->loan_number,
                    'remaining_principal' => $remainingPrincipal,
                    'foreclosure_charges' => $charges,
                    'total_due' => $totalDue,
                ],
                logName: 'loan'
            );

            return $loan->fresh();
        });
    }

    public function approve(Loan $loan, ?string $notes = null): Loan
    {
        if ($loan->status !== 'pending') {
            throw new HttpException(422, 'Only pending loans can be approved.');
        }

        $loan->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $notes ?? $loan->notes,
        ]);

        $this->generateEmiSchedule($loan);

        Event::dispatch(new LoanApproved($loan));

        $this->activityLogService->log(
            description: 'Loan approved #' . $loan->id,
            event: 'approved',
            subjectType: Loan::class,
            subjectId: $loan->id,
            properties: [
                'loan_number' => $loan->loan_number,
                'loan_amount' => $loan->loan_amount,
                'interest_rate' => $loan->interest_rate,
                'tenure_months' => $loan->loan_tenure_months,
                'approved_by' => auth()->id(),
            ],
            logName: 'loan'
        );

        return $loan;
    }

    protected function getCalculator(string $interestType): EMICalculatorInterface
    {
        return match ($interestType) {
            'flat' => $this->flatRate,
            default => $this->reducingBalance,
        };
    }

    protected function generateLoanNumber(): string
    {
        return 'LN-' . strtoupper(Str::random(8));
    }
}
