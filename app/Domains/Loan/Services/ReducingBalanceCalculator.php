<?php

declare(strict_types=1);

namespace App\Domains\Loan\Services;

class ReducingBalanceCalculator implements EMICalculatorInterface
{
    public function calculate(float $principal, float $annualRate, int $tenureMonths): array
    {
        $monthlyRate = ($annualRate / 100) / 12;

        if ($monthlyRate == 0) {
            $emiAmount = $principal / $tenureMonths;
        } else {
            $factor = (1 + $monthlyRate) ** $tenureMonths;
            $emiAmount = $principal * $monthlyRate * $factor / ($factor - 1);
        }

        $schedule = [];
        $outstanding = $principal;

        for ($i = 1; $i <= $tenureMonths; $i++) {
            $interestAmount = $outstanding * $monthlyRate;
            $principalAmount = $emiAmount - $interestAmount;
            $outstanding -= $principalAmount;

            if ($i === $tenureMonths) {
                $principalAmount += $outstanding;
                $outstanding = 0;
            }

            $schedule[] = [
                'emi_number' => $i,
                'principal_amount' => round($principalAmount, 2),
                'interest_amount' => round($interestAmount, 2),
                'total_amount' => round($principalAmount + $interestAmount, 2),
                'outstanding_balance' => round(max($outstanding, 0), 2),
            ];
        }

        return $schedule;
    }
}
