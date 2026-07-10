<?php

namespace Database\Seeders;

use App\Domains\Auth\Models\User;
use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Loan\Services\LoanService;
use App\Domains\Payment\Models\Payment;
use App\Domains\Tenant\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'company_name' => 'Acme Finance Corp',
            'timezone' => 'Asia/Kolkata',
            'default_currency' => 'INR',
            'payment_gateway_config' => [
                'gateway' => 'stripe',
                'secret_key' => config('services.stripe.secret', 'sk_test_placeholder'),
                'webhook_secret' => config('services.stripe.webhook_secret', 'whsec_placeholder'),
            ],
            'status' => 'active',
        ]);

        $this->createUsers($tenant);
        $borrowers = $this->createBorrowers($tenant);
        $this->createLoans($tenant, $borrowers);
    }

    private function createUsers(Tenant $tenant): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $superAdmin->assignRole('super-admin');

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ])->assignRole('admin');

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rahul Sharma',
            'email' => 'officer@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ])->assignRole('loan-officer');

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Amit Patel',
            'email' => 'borrower@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ])->assignRole('borrower');
    }

    /** @return array<int, Borrower> */
    private function createBorrowers(Tenant $tenant): array
    {
        $borrowersData = [
            ['full_name' => 'Priya Venkatesh', 'email' => 'priya@example.com', 'mobile_number' => '+919876543210', 'date_of_birth' => '1990-05-15', 'national_id' => 'AADHAR123456'],
            ['full_name' => 'Rajesh Kumar', 'email' => 'rajesh@example.com', 'mobile_number' => '+919876543211', 'date_of_birth' => '1985-11-20', 'national_id' => 'AADHAR123457'],
            ['full_name' => 'Sunita Reddy', 'email' => 'sunita@example.com', 'mobile_number' => '+919876543212', 'date_of_birth' => '1992-08-03', 'national_id' => 'AADHAR123458'],
            ['full_name' => 'Arun Nair', 'email' => 'arun@example.com', 'mobile_number' => '+919876543213', 'date_of_birth' => '1988-01-25', 'national_id' => 'AADHAR123459'],
        ];

        $borrowers = [];
        foreach ($borrowersData as $data) {
            $borrowers[] = Borrower::create(['tenant_id' => $tenant->id] + $data);
        }

        return $borrowers;
    }

    /** @param array<int, Borrower> $borrowers */
    private function createLoans(Tenant $tenant, array $borrowers): void
    {
        $admin = User::where('email', 'admin@test.com')->first();
        Auth::login($admin);

        $loanService = app(LoanService::class);

        $loansData = [
            [
                'borrower' => $borrowers[0],
                'loan_amount' => 500000,
                'interest_rate' => 12.5,
                'loan_tenure_months' => 24,
                'interest_type' => 'reducing',
                'status' => 'active',
            ],
            [
                'borrower' => $borrowers[1],
                'loan_amount' => 200000,
                'interest_rate' => 10.0,
                'loan_tenure_months' => 12,
                'interest_type' => 'flat',
                'status' => 'active',
            ],
            [
                'borrower' => $borrowers[2],
                'loan_amount' => 1000000,
                'interest_rate' => 9.5,
                'loan_tenure_months' => 36,
                'interest_type' => 'reducing',
                'status' => 'pending',
            ],
            [
                'borrower' => $borrowers[3],
                'loan_amount' => 75000,
                'interest_rate' => 15.0,
                'loan_tenure_months' => 6,
                'interest_type' => 'flat',
                'status' => 'completed',
            ],
        ];

        foreach ($loansData as $i => $data) {
            $loan = Loan::create([
                'tenant_id' => $tenant->id,
                'borrower_id' => $data['borrower']->id,
                'loan_number' => 'LN-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'loan_type' => 'personal',
                'loan_amount' => $data['loan_amount'],
                'interest_rate' => $data['interest_rate'],
                'loan_tenure_months' => $data['loan_tenure_months'],
                'emi_start_date' => now()->addDays(30),
                'processing_fee' => round($data['loan_amount'] * 0.01, 2),
                'currency' => 'INR',
                'interest_type' => $data['interest_type'],
                'status' => $data['status'],
            ]);

            if ($data['status'] === 'active') {
                $loanService->generateEmiSchedule($loan);

                $loan->update([
                    'status' => 'active',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                ]);

                $this->createPayment($loan, $i === 0 ? 1 : 2);
            }

            if ($data['status'] === 'completed') {
                $loanService->generateEmiSchedule($loan);

                $loan->update([
                    'status' => 'completed',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                ]);

                $loan->emiSchedules()->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        Auth::logout();
    }

    private function createPayment(Loan $loan, int $paidEmis): void
    {
        $emis = $loan->emiSchedules()->orderBy('emi_number')->take($paidEmis)->get();

        foreach ($emis as $emi) {
            Payment::create([
                'tenant_id' => $loan->tenant_id,
                'loan_id' => $loan->id,
                'emi_schedule_id' => $emi->id,
                'borrower_id' => $loan->borrower_id,
                'transaction_id' => 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 12)),
                'gateway' => 'stripe',
                'gateway_payment_id' => 'pi_' . substr(md5(uniqid()), 0, 16),
                'amount' => $emi->total_amount,
                'currency' => 'INR',
                'status' => 'completed',
                'payment_type' => 'full',
                'paid_at' => Carbon::parse($emi->due_date)->subDays(2),
            ]);

            $emi->update(['status' => 'paid', 'paid_at' => Carbon::parse($emi->due_date)->subDays(2)]);
        }
    }
}
