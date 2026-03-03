<?php

declare(strict_types=1);

namespace App\Services;

class AuthService
{
    public static function makeToken(int $userId): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'dev-secret';
        $payload = base64_encode(json_encode(['uid' => $userId, 'iat' => time()], JSON_THROW_ON_ERROR));

        return $payload.'.'.hash_hmac('sha256', $payload, $secret);
    }

    public static function parseToken(?string $bearer): ?int
    {
        if (! $bearer || !str_starts_with($bearer, 'Bearer ')) {
            return null;
        }

        $token = substr($bearer, 7);
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $sig] = $parts;
        $secret = $_ENV['JWT_SECRET'] ?? 'dev-secret';
        $valid = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($valid, $sig)) {
            return null;
        }

        $data = json_decode(base64_decode($payload) ?: '', true);

        return isset($data['uid']) ? (int)$data['uid'] : null;
    }
}
