<?php

declare(strict_types=1);

namespace App\Domains\Loan\Services;

class FlatRateCalculator implements EMICalculatorInterface
{
    public function calculate(float $principal, float $annualRate, int $tenureMonths): array
    {
        $totalInterest = $principal * ($annualRate / 100) * ($tenureMonths / 12);
        $totalAmount = $principal + $totalInterest;
        $emiAmount = $totalAmount / $tenureMonths;
        $principalPerEmi = $principal / $tenureMonths;
        $interestPerEmi = $totalInterest / $tenureMonths;

        $schedule = [];
        $outstanding = $principal;

        for ($i = 1; $i <= $tenureMonths; $i++) {
            $outstanding -= $principalPerEmi;

            $isLast = $i === $tenureMonths;
            $principalAmt = $isLast
                ? round($principal - ($principalPerEmi * ($tenureMonths - 1)), 2)
                : round($principalPerEmi, 2);

            $outstanding = $isLast ? 0.0 : $outstanding;

            $schedule[] = [
                'emi_number' => $i,
                'principal_amount' => $principalAmt,
                'interest_amount' => round($interestPerEmi, 2),
                'total_amount' => round($emiAmount, 2),
                'outstanding_balance' => round(max($outstanding, 0), 2),
            ];
        }

        return $schedule;
    }
}
