<?php

namespace App\Http\Requests;

use App\Models\AiSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorización por `can:ai.manage` en la ruta
    }

    public function rules(): array
    {
        return [
            'provider'  => ['required', Rule::in(AiSetting::PROVIDERS)],
            'model'     => ['nullable', 'string', 'max:128'],
            'api_key'   => ['nullable', 'string', 'max:512'],
            'base_url'  => ['nullable', 'url', 'max:255', function (string $attr, mixed $value, \Closure $fail): void {
                if ($value === null) {
                    return;
                }

                $parsed = parse_url((string) $value);
                $scheme = strtolower($parsed['scheme'] ?? '');
                $host   = strtolower($parsed['host'] ?? '');

                if ($scheme !== 'https') {
                    $fail('The base_url must use HTTPS.');
                    return;
                }

                // Resolve to IP for hostname-based checks.
                $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

                if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                    // Could not resolve — reject to be safe.
                    $fail('The base_url host could not be resolved.');
                    return;
                }

                foreach (self::PRIVATE_CIDRS as [$network, $prefix]) {
                    if ($this->ipInCidr($ip, $network, $prefix)) {
                        $fail('The base_url must not point to a private or internal address.');
                        return;
                    }
                }
            }],
            'is_active'        => ['boolean'],
            'system_prompt'    => ['nullable', 'string', 'max:4000'],
            'context_messages' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    // RFC1918 + loopback + link-local + IPv6 privados.
    private const PRIVATE_CIDRS = [
        ['10.0.0.0',    8],
        ['172.16.0.0',  12],
        ['192.168.0.0', 16],
        ['127.0.0.0',   8],
        ['169.254.0.0', 16],
        ['::1',         128],
        ['fc00::',      7],   // fd00::/8 + fc00::/8
        ['fe80::',      10],
    ];

    private function ipInCidr(string $ip, string $network, int $prefix): bool
    {
        $ipBin  = inet_pton($ip);
        $netBin = inet_pton($network);

        if ($ipBin === false || $netBin === false) {
            return false;
        }

        // Handle IPv4-in-IPv6 mismatch.
        if (strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $bytes = (int) ceil($prefix / 8);
        $mask  = str_repeat("\xff", (int) floor($prefix / 8));
        $rem   = $prefix % 8;
        if ($rem > 0) {
            $mask .= chr(0xff & (0xff << (8 - $rem)));
        }
        $mask = str_pad($mask, strlen($ipBin), "\x00");

        return ($ipBin & $mask) === ($netBin & $mask);
    }
}
