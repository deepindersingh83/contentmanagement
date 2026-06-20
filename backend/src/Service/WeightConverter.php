<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Normalises supplier weights (which arrive in g/kg/lb/oz) to grams so the
 * master catalogue can store a single comparable unit (requirement #5).
 */
final class WeightConverter
{
    private const TO_GRAMS = [
        'g' => 1.0,
        'kg' => 1000.0,
        'lb' => 453.59237,
        'oz' => 28.349523125,
    ];

    public static function toGrams(?string $value, ?string $unit): ?string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $factor = self::TO_GRAMS[strtolower((string) $unit)] ?? null;
        if ($factor === null) {
            return null;
        }

        return number_format((float) $value * $factor, 2, '.', '');
    }

    /** @return list<string> */
    public static function units(): array
    {
        return array_keys(self::TO_GRAMS);
    }
}
