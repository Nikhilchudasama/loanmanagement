<?php

declare(strict_types=1);

namespace App\Domains\Loan\Services;

interface EMICalculatorInterface
{
    public function calculate(float $principal, float $annualRate, int $tenureMonths): array;
}
