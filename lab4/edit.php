<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Якщо форму відправлено, оновлюємо дані
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $duration = $_POST['duration_min'];
    $calories = $_POST['calories_burned'];
    $date = $_POST['workout_date'];

    $stmt = $pdo->prepare('UPDATE workouts SET type = :type, duration_min = :duration, calories_burned = :calories, workout_date = :date WHERE id = :id');
    $stmt->execute([
        ':type' => $type,
        ':duration' => $duration,
        ':calories' => $calories,
        ':date' => $date,
        ':id' => $id
    ]);

    header('Location: index.php');
    exit;
}

// Отримуємо поточні дані тренування для виводу у форму
$stmt = $pdo->prepare('SELECT * FROM workouts WHERE id = :id');
$stmt->execute([':id' => $id]);
$workout = $stmt->fetch();

if (!$workout) {
    die('Тренування не знайдено');
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагувати тренування</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Редагувати тренування</h2>
    <form action="edit.php?id=<?= $workout['id'] ?>" method="POST">
        <div class="form-group">
            <label>Тип тренування:</label>
            <input type="text" name="type" value="<?= htmlspecialchars($workout['type']) ?>" required>
        </div>
        <div class="form-group">
            <label>Тривалість (хв):</label>
            <input type="number" name="duration_min" value="<?= htmlspecialchars($workout['duration_min']) ?>" required>
        </div>
        <div class="form-group">
            <label>Калорії:</label>
            <input type="number" name="calories_burned" value="<?= htmlspecialchars($workout['calories_burned']) ?>" required>
        </div>
        <div class="form-group">
            <label>Дата:</label>
            <input type="date" name="workout_date" value="<?= htmlspecialchars($workout['workout_date']) ?>" required>
        </div>
        <button type="submit">Зберегти зміни</button>
        <a href="index.php" style="margin-left: 10px;">Скасувати</a>
    </form>
</body>
</html>