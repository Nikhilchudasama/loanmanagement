<?php

namespace App\Domains\Payment\Gateways;

use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayInterface
{
    protected StripeClient $stripe;

    protected ?string $webhookSecret;

    public function __construct(array $config)
    {
        $this->stripe = new StripeClient($config['secret_key'] ?? '');
        $this->webhookSecret = $config['webhook_secret'] ?? null;
    }

    public function setStripeClient(StripeClient $client): static
    {
        $this->stripe = $client;

        return $this;
    }

    public function processWebhook(Request $request): array
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if ($this->webhookSecret && $sigHeader) {
            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
            } catch (SignatureVerificationException) {
                return ['error' => 'Invalid signature', 'status' => 'failed'];
            }
        } else {
            $event = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Invalid payload', 'status' => 'failed'];
            }
        }

        if (is_object($event) && isset($event->type)) {
            $eventType = $event->type;
            $data = (array) $event->data->object;
        } elseif (is_array($event)) {
            $eventType = $event['type'] ?? '';
            $data = $event['data']['object'] ?? [];
        } else {
            return ['error' => 'Unexpected event format', 'status' => 'failed'];
        }

        return [
            'event' => $eventType,
            'gateway_payment_id' => $data['id'] ?? null,
            'status' => match ($eventType) {
                'checkout.session.completed' => 'completed',
                'payment_intent.succeeded' => 'completed',
                'payment_intent.payment_failed' => 'failed',
                default => 'pending',
            },
            'amount' => ($data['amount_total'] ?? $data['amount'] ?? 0) / 100,
            'currency' => strtoupper($data['currency'] ?? 'usd'),
        ];
    }

    public function createPaymentLink(float $amount, string $currency, array $metadata = []): string | array
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => 'Loan EMI Payment',
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => $metadata,
            'success_url' => $metadata['success_url'] ?? 'https://example.com/success',
            'cancel_url' => $metadata['cancel_url'] ?? 'https://example.com/cancel',
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }

    public function verifyPayment(string $gatewayPaymentId): array
    {
        $session = $this->stripe->checkout->sessions->retrieve($gatewayPaymentId);

        return [
            'status' => $session->payment_status === 'paid' ? 'completed' : 'pending',
            'amount' => $session->amount_total / 100,
            'currency' => strtoupper($session->currency),
        ];
    }

    public function refundPayment(string $gatewayPaymentId, float $amount): array
    {
        $refund = $this->stripe->refunds->create([
            'payment_intent' => $gatewayPaymentId,
            'amount' => (int) ($amount * 100),
        ]);

        return [
            'id' => $refund->id,
            'status' => $refund->status,
            'amount' => $refund->amount / 100,
        ];
    }
}
