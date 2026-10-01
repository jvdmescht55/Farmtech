<?php

namespace App\Support;

use App\Models\Animal;

/**
 * Farm "birthday" numbers: YYMMNN — year, month, and the how-many-th animal
 * born that month. 250912 = the 12th lamb of September 2025.
 */
class BirthdayId
{
    public static function isBirthday(?string $id): bool
    {
        return (bool) preg_match('/^(\d{2})(0[1-9]|1[0-2])(\d{2})$/', (string) $id);
    }

    /** First of the birth month, for an animal registered with a birthday number. */
    public static function birthDate(?string $id): ?string
    {
        if (! self::isBirthday($id)) {
            return null;
        }
        $year = 2000 + (int) substr($id, 0, 2);
        if ($year > (int) date('Y') + 1) {
            return null;
        }

        return sprintf('%04d-%02d-01', $year, (int) substr($id, 2, 2));
    }

    /** Next free number for a month, e.g. next(3, '2510') → '251004' if 01–03 are taken. */
    public static function next(int $userId, ?string $yymm = null): string
    {
        $yymm = $yymm && preg_match('/^\d{2}(0[1-9]|1[0-2])$/', $yymm) ? $yymm : date('ym');
        $taken = Animal::where('user_id', $userId)->where('visual_id', 'like', $yymm.'__')->pluck('visual_id')
            ->filter(fn ($v) => self::isBirthday($v))->map(fn ($v) => (int) substr($v, 4, 2));
        $n = ($taken->max() ?? 0) + 1;

        return $yymm.str_pad((string) min($n, 99), 2, '0', STR_PAD_LEFT);
    }

    /** "12th born in September 2025" */
    public static function describe(?string $id): ?string
    {
        if (! self::isBirthday($id)) {
            return null;
        }
        $n = (int) substr($id, 4, 2);
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n.$suffix.' born in '.date('F', mktime(0, 0, 0, (int) substr($id, 2, 2), 1)).' 20'.substr($id, 0, 2);
    }
}
