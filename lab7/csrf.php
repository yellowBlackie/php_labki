<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


function getCsrfToken(): string
{
    return $_SESSION['csrf_token'];
}


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
