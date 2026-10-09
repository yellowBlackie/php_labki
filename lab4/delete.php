<?php

require_once 'db.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не підтримується.');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$id = $_POST['id'] ?? null;

if (
    filter_var($id, FILTER_VALIDATE_INT) === false ||
    (int)$id <= 0
) {
    http_response_code(400);
    exit('Некоректний id.');
}

$stmt = $pdo->prepare(
    'DELETE FROM workouts
     WHERE id = :id'
);

$stmt->execute([
    ':id' => (int)$id,
]);

header('Location: index.php');
exit;
