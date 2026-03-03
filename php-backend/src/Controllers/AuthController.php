<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthController
{
    public function register(Request $request, Response $response): void
    {
        $body = $request->body();
        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if (!$name || !$email || strlen($password) < 8) {
            $response->json(['message' => 'Invalid payload'], 422);
            return;
        }

        $pdo = Database::pdo();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $response->json(['message' => 'Email already exists'], 422);
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO users(name, email, password_hash, is_super_admin, created_at) VALUES(?,?,?,?,NOW())');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 0]);

        $id = (int)$pdo->lastInsertId();
        $response->json(['token' => AuthService::makeToken($id), 'user' => ['id' => $id, 'name' => $name, 'email' => $email]], 201);
    }

    public function login(Request $request, Response $response): void
    {
        $body = $request->body();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_super_admin FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $response->json(['message' => 'Invalid credentials'], 401);
            return;
        }

        unset($user['password_hash']);
        $response->json(['token' => AuthService::makeToken((int)$user['id']), 'user' => $user]);
    }
}
