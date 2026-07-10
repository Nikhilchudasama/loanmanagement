<?php

use App\Domains\Loan\Services\FlatRateCalculator;
use App\Domains\Loan\Services\ReducingBalanceCalculator;

it('calculates reducing balance emi correctly', function (): void {
    $calculator = new ReducingBalanceCalculator();

    $schedule = $calculator->calculate(100000, 12, 12);

    expect($schedule)->toHaveCount(12);

    expect($schedule[0]['principal_amount'])->toEqual(7884.88);
    expect($schedule[0]['interest_amount'])->toEqual(1000.00);
    expect($schedule[0]['total_amount'])->toEqual(8884.88);
    expect($schedule[0]['outstanding_balance'])->toEqual(92115.12);

    expect($schedule[11]['principal_amount'])->toEqual(8796.91);
    expect($schedule[11]['interest_amount'])->toEqual(87.97);
    expect($schedule[11]['total_amount'])->toEqual(8884.88);
    expect($schedule[11]['outstanding_balance'])->toEqual(0.00);

    $totalPrincipal = array_sum(array_column($schedule, 'principal_amount'));
    expect($totalPrincipal)->toBeGreaterThanOrEqual(99999.99);
    expect($totalPrincipal)->toBeLessThanOrEqual(100000.01);

    $emis = array_column($schedule, 'total_amount');
    $firstEmi = $emis[0];
    for ($i = 0; $i < 11; $i++) {
        expect($emis[$i])->toEqual($firstEmi);
    }
});

it('calculates flat rate emi correctly', function (): void {
    $calculator = new FlatRateCalculator();

    $schedule = $calculator->calculate(100000, 12, 12);

    expect($schedule)->toHaveCount(12);

    foreach ($schedule as $item) {
        expect($item['principal_amount'])->toEqual(8333.33);
        expect($item['interest_amount'])->toEqual(1000.00);
        expect($item['total_amount'])->toEqual(9333.33);
    }

    expect($schedule[0]['outstanding_balance'])->toEqual(91666.67);
    expect($schedule[11]['outstanding_balance'])->toEqual(0.00);
});

it('handles zero interest rate', function (): void {
    $calculator = new ReducingBalanceCalculator();

    $schedule = $calculator->calculate(12000, 0, 12);

    expect($schedule)->toHaveCount(12);
    expect($schedule[0]['total_amount'])->toEqual(1000.00);
    expect($schedule[0]['interest_amount'])->toEqual(0.00);
    expect($schedule[11]['outstanding_balance'])->toEqual(0.00);
});

it('handles single month tenure', function (): void {
    $calculator = new ReducingBalanceCalculator();

    $schedule = $calculator->calculate(10000, 12, 1);

    expect($schedule)->toHaveCount(1);
    expect($schedule[0]['principal_amount'])->toEqual(10000.00);
    expect($schedule[0]['interest_amount'])->toEqual(100.00);
    expect($schedule[0]['total_amount'])->toEqual(10100.00);
    expect($schedule[0]['outstanding_balance'])->toEqual(0.00);
});

it('reducing balance interest total is less than flat rate', function (): void {
    $principal = 100000;
    $rate = 12;
    $tenure = 12;

    $flat = new FlatRateCalculator();
    $reducing = new ReducingBalanceCalculator();

    $flatSchedule = $flat->calculate($principal, $rate, $tenure);
    $reducingSchedule = $reducing->calculate($principal, $rate, $tenure);

    $flatInterest = array_sum(array_column($flatSchedule, 'interest_amount'));
    $reducingInterest = array_sum(array_column($reducingSchedule, 'interest_amount'));

    expect($flatInterest)->toBeGreaterThan($reducingInterest);
    expect(round($flatInterest, 2))->toEqual(12000.00);
    expect($reducingInterest)->toBeGreaterThanOrEqual(6618.52);
    expect($reducingInterest)->toBeLessThanOrEqual(6618.54);
});
