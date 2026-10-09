<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// Створюємо CSRF-токен один раз для поточної сесії
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


// Повертає поточний CSRF-токен
function getCsrfToken(): string
{
    return $_SESSION['csrf_token'];
}


// Перевіряє токен для POST-запитів
function verifyCsrfToken(array $data): void
{
    $sessionToken =
        $_SESSION['csrf_token'] ?? '';

    $requestToken =
        $data['csrf_token'] ?? '';

    if (
        $sessionToken === '' ||
        $requestToken === '' ||
        !hash_equals(
            $sessionToken,
            $requestToken
        )
    ) {
        sendError(
            'Недійсний CSRF-токен.',
            403
        );
    }
}
