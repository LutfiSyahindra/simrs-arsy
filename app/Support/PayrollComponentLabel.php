<?php

namespace App\Support;

final class PayrollComponentLabel
{
    public const DEFAULT_SALARY_LABEL = 'Gaji Pokok';

    public const DEFAULT_PAID_SALARY_LABEL = 'Gaji Dibayarkan';

    public const UGD_CONTRACT_SALARY_LABEL = 'Upah STR';

    public const UGD_CONTRACT_PAID_SALARY_LABEL = 'Kehadiran';

    public static function salaryLabel(?string $position, ?string $employmentStatus): string
    {
        return self::isUgdContractDoctor($position, $employmentStatus)
            ? self::UGD_CONTRACT_SALARY_LABEL
            : self::DEFAULT_SALARY_LABEL;
    }

    public static function paidSalaryLabel(?string $position, ?string $employmentStatus): string
    {
        return self::isUgdContractDoctor($position, $employmentStatus)
            ? self::UGD_CONTRACT_PAID_SALARY_LABEL
            : self::DEFAULT_PAID_SALARY_LABEL;
    }

    public static function isUgdContractDoctor(?string $position, ?string $employmentStatus): bool
    {
        return self::normalize($position) === self::normalize('Dokter Unit Gawat Darurat')
            && self::normalizeStatus($employmentStatus) === 'FT';
    }

    private static function normalize(?string $value): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private static function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim((string) $status));

        return $status === 'KONTRAK' ? 'FT' : $status;
    }
}
