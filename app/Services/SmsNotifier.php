<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsNotifier
{
    public function send(string $phone, string $message): void
    {
        $config = config('services.sms');

        if (empty($config['url']) || empty($config['api_key'])) {
            Log::warning('SMS notification skipped because provider configuration is missing.', [
                'phone' => $this->redactPhone($phone),
            ]);

            return;
        }

        try {
            Http::asJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $config['api_key'],
                ])
                ->timeout((int) ($config['timeout'] ?? 5))
                ->post($config['url'], [
                    'to' => $phone,
                    'body' => $message,
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('SMS notification failed', [
                'phone' => $this->redactPhone($phone),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function redactPhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if ($normalized === '') {
            return 'redacted';
        }

        $length = strlen($normalized);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($normalized, 0, 2) . str_repeat('*', max($length - 6, 0)) . substr($normalized, -4);
    }
}