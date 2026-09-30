<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $duration = $_POST['duration_min'];
    $calories = $_POST['calories_burned'];
    $date = $_POST['workout_date'];

    // Використання підготовлених запитів для безпеки
    $stmt = $pdo->prepare('INSERT INTO workouts (type, duration_min, calories_burned, workout_date) VALUES (:type, :duration, :calories, :date)');
    $stmt->execute([
        ':type' => $type,
        ':duration' => $duration,
        ':calories' => $calories,
        ':date' => $date
    ]);

    // Перенаправлення після додавання
    header('Location: index.php');
    exit;
}
?>