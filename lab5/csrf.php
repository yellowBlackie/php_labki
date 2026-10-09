<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Створюємо CSRF-токен один раз для поточної сесії
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Повертає поточний CSRF-токен
function getCsrfToken(): string
{
    return $_SESSION['csrf_token'];
}


// Перевіряє CSRF-токен у POST-запиті
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

        echo json_encode([
            'error' => 'Недійсний CSRF-токен.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
