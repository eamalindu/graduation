<?php
declare(strict_types=1);

/**
 * Returns 94XXXXXXXXX for a Sri Lankan mobile, or null (foreign / invalid / landline).
 */
function lkMobile(?string $raw): ?string
{
    $raw = trim((string)$raw);
    if ($raw === '') return null;

    $digits = preg_replace('/\D+/', '', $raw);

    // "+" with anything other than 94 = foreign
    if (str_starts_with($raw, '+') && !str_starts_with($digits, '94')) return null;

    if (str_starts_with($digits, '0094'))      $digits = substr($digits, 4);
    elseif (str_starts_with($digits, '94'))    $digits = substr($digits, 2);
    elseif (str_starts_with($digits, '0'))     $digits = substr($digits, 1);

    // Local mobiles: 7XXXXXXXX (9 digits after country code)
    return preg_match('/^7\d{8}$/', $digits) ? '94' . $digits : null;
}