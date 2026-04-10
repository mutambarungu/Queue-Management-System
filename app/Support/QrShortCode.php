<?php

namespace App\Support;

class QrShortCode
{
    private const SIGNATURE_LENGTH = 12;

    public static function encode(int $officeId, ?int $subOfficeId = null): string
    {
        $officePart = self::toBase36($officeId);

        if (filled($subOfficeId)) {
            return $officePart . '.' . self::toBase36((int) $subOfficeId);
        }

        return $officePart;
    }

    /**
     * @return array{office_id:int, sub_office_id:int|null}|null
     */
    public static function decode(string $code): ?array
    {
        $parts = explode('.', strtolower(trim($code)));
        if (count($parts) === 0 || count($parts) > 2) {
            return null;
        }

        $officePart = $parts[0] ?? '';
        if ($officePart === '' || !self::isBase36($officePart)) {
            return null;
        }

        $officeId = self::fromBase36($officePart);
        if ($officeId < 1) {
            return null;
        }

        $subOfficeId = null;
        if (count($parts) === 2) {
            $subPart = $parts[1] ?? '';
            if ($subPart === '' || !self::isBase36($subPart)) {
                return null;
            }
            $decodedSub = self::fromBase36($subPart);
            if ($decodedSub < 1) {
                return null;
            }
            $subOfficeId = $decodedSub;
        }

        return [
            'office_id' => $officeId,
            'sub_office_id' => $subOfficeId,
        ];
    }

    public static function sign(string $code): string
    {
        $raw = hash_hmac('sha256', $code, self::signingKey(), true);
        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        return substr($encoded, 0, self::SIGNATURE_LENGTH);
    }

    public static function verify(string $code, string $signature): bool
    {
        $expected = self::sign($code);

        return hash_equals($expected, $signature);
    }

    private static function signingKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }

    private static function isBase36(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-z]+$/i', $value);
    }

    private static function toBase36(int $value): string
    {
        return strtolower(base_convert((string) $value, 10, 36));
    }

    private static function fromBase36(string $value): int
    {
        return (int) base_convert($value, 36, 10);
    }
}
