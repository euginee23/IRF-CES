<?php

namespace App\Services\Sms;

class PhoneNumber
{
    /**
     * Normalise a phone number to E.164 (e.g. "+639171234567").
     *
     * Customer numbers reach the database in mixed formats: the landing page
     * formats them as "+63 917 123 4567" while the job order forms expect
     * "09171234567". Both must resolve to the same sendable number.
     *
     * @return string|null Null when the input holds no usable digits.
     */
    public static function toE164(?string $number, string $countryCode = '63'): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number) ?? '';

        if ($digits === '') {
            return null;
        }

        // Trim the national trunk prefix: "0917..." -> "917..."
        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');

            if ($digits === '') {
                return null;
            }
        }

        // Add the country code unless the number already carries it
        if (! str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;
        }

        return '+' . $digits;
    }
}
