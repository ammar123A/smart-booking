<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SenangPayService
{
    public function generateProviderRef(): string
    {
        return 'SP-'.Str::ulid()->toBase32();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function buildPaymentUrl(array $params): string
    {
        $base = rtrim((string) config('senangpay.payment_base_url'), '/');

        return $base.'?'.http_build_query($params);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function verifyCallbackSignature(array $payload): bool
    {
        if (! config('senangpay.verify_callback_signature')) {
            return true;
        }

        $secret = (string) config('senangpay.secret_key');
        if ($secret === '') {
            return false;
        }

        $statusId = (string) Arr::get($payload, 'status_id', '');
        $orderId = (string) Arr::get($payload, 'order_id', '');
        $transactionId = (string) Arr::get($payload, 'transaction_id', '');
        $msg = (string) Arr::get($payload, 'msg', '');
        $hash = (string) Arr::get($payload, 'hash', '');

        if ($orderId === '' || $hash === '') {
            return false;
        }

        $computed = md5($secret.$statusId.$orderId.$transactionId.$msg);

        return hash_equals($computed, $hash);
    }

    public function isPaidStatus(string $statusId): bool
    {
        return $statusId === '1' || strtolower($statusId) === 'paid';
    }

    public function mapCallbackToPaymentStatus(string $statusId): string
    {
        return $this->isPaidStatus($statusId)
            ? Payment::STATUS_PAID
            : Payment::STATUS_FAILED;
    }
}
