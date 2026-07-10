<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function createPaymentLink(float $amount, string $currency, array $metadata = []): string | array;
    public function processWebhook(Request $request): array;
    public function verifyPayment(string $gatewayPaymentId): array;
    public function refundPayment(string $gatewayPaymentId, float $amount): array;
}
