<?php

namespace App\Services;

use App\Models\JobOrder;

/**
 * Short repair tracking codes, e.g. R7K4M2.
 *
 * The job order number (JO-20260924-0001) is fine on paper but awkward to
 * read over the phone or type on a small screen. This is the code the
 * customer is actually given.
 *
 * The alphabet omits 0/O and 1/I/L, because the code is read aloud and
 * written down by hand, and those pairs are where that goes wrong.
 */
class TrackingCode
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public const LENGTH = 6;

    /**
     * A code that is not already in use.
     *
     * Random and checked, rather than the count-then-increment loop the job
     * order numbering uses: with 31^6 codes a collision is vanishingly rare,
     * and the unique index on the column is the real guarantee. Callers
     * should still be prepared for the insert to be the thing that fails.
     */
    public static function generate(): string
    {
        do {
            $code = self::random();
        } while (JobOrder::where('tracking_code', $code)->exists());

        return $code;
    }

    public static function random(): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Tidy up what a customer typed.
     *
     * Case and stray spaces or dashes are not the customer's problem.
     */
    public static function normalise(string $input): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');
    }

    /** Whether a string looks like one of our codes. */
    public static function looksValid(string $input): bool
    {
        $code = self::normalise($input);

        return strlen($code) === self::LENGTH
            && strspn($code, self::ALPHABET) === self::LENGTH;
    }
}
