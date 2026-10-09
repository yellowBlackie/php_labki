<?php

require_once 'db.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не підтримується.');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$type = trim($_POST['type'] ?? '');
$duration = $_POST['duration_min'] ?? null;
$calories = $_POST['calories_burned'] ?? null;
$date = trim($_POST['workout_date'] ?? '');

if ($type === '') {
    http_response_code(400);
    exit('Поле type є обов’язковим.');
}

if ($duration === null || $duration === '') {
    http_response_code(400);
    exit('Поле duration_min є обов’язковим.');
}

if ($calories === null || $calories === '') {
    http_response_code(400);
    exit('Поле calories_burned є обов’язковим.');
}

if ($date === '') {
    http_response_code(400);
    exit('Поле workout_date є обов’язковим.');
}

if (strlen($type) > 100) {
    http_response_code(400);
    exit('Поле type є занадто довгим.');
}

if (
    filter_var($duration, FILTER_VALIDATE_INT) === false ||
    (int)$duration <= 0
) {
    http_response_code(400);
    exit('Тривалість має бути додатним цілим числом.');
}

if (
    filter_var($calories, FILTER_VALIDATE_INT) === false ||
    (int)$calories < 0
) {
    http_response_code(400);
    exit('Калорії мають бути невід’ємним цілим числом.');
}

$dateObject = DateTime::createFromFormat('Y-m-d', $date);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $date
) {
    http_response_code(400);
    exit('Дата має бути у форматі YYYY-MM-DD.');
}

$stmt = $pdo->prepare(
    'INSERT INTO workouts
     (type, duration_min, calories_burned, workout_date)
     VALUES
     (:type, :duration, :calories, :date)'
);

$stmt->execute([
    ':type' => $type,
    ':duration' => (int)$duration,
    ':calories' => (int)$calories,
    ':date' => $date,
]);

header('Location: index.php');
exit;
