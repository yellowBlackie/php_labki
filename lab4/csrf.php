<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfToken(): string
{
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        $sessionToken === '' ||
        $token === null ||
        $token === '' ||
        !hash_equals($sessionToken, $token)
    ) {
        http_response_code(403);
        exit('Недійсний CSRF-токен.');
    }
}
