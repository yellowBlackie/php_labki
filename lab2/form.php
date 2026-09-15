<?php
// Увімкнення відображення помилок на час розробки
error_reporting(E_ALL);
ini_set('display_errors', '1');

$errors = [];
$successMessage = '';

$type = '';
$durationMin = '';
$caloriesBurned = '';
$date = '';

// крок 3 серверна обробка й валідація
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // отримуємо дані, очищаючи їх від зайвих пробілів
    $type = trim($_POST['type'] ?? '');
    $durationMin = trim($_POST['durationMin'] ?? '');
    $caloriesBurned = trim($_POST['caloriesBurned'] ?? '');
    $date = trim($_POST['date'] ?? '');

    // валідація типу тренування (обов'язкове поле)
    if ($type === '') {
        $errors['type'] = 'Оберіть тип тренування.';
    }

    // валідація durationMin (число від 1 до 300)
    if (!is_numeric($durationMin) || $durationMin < 1 || $durationMin > 300) {
        $errors['durationMin'] = 'Тривалість має бути числом від 1 до 300.';
    }

    // валідація caloriesBurned (якщо введено, має бути числом >= 0)
    if ($caloriesBurned !== '' && (!is_numeric($caloriesBurned) || $caloriesBurned < 0)) {
        $errors['caloriesBurned'] = 'Калорії мають бути додатним числом.';
    }

    // валідація date (коректна дата, не пізніша за сьогодні)
    if ($date === '') {
        $errors['date'] = 'Дата обов\'язкова.';
    } else {
        $parsedDate = strtotime($date);
        if (!$parsedDate) {
            $errors['date'] = 'Некоректний формат дати.';
        } else {
            $today = strtotime(date('Y-m-d'));
            if ($parsedDate > $today) {
                $errors['date'] = 'Дата тренування не може бути в майбутньому.';
            }
        }
    }

    // крок 4 показати результат обробки
    if (empty($errors)) {
        // захист від XSS за допомогою htmlspecialchars()
        $safeType = htmlspecialchars($type);
        $safeDuration = htmlspecialchars($durationMin);
        $safeDate = htmlspecialchars($date);
        
        $successMessage = "Дані успішно збережено! Тренування: <strong>$safeType</strong>, Тривалість: $safeDuration хв, Дата: $safeDate.";
        
        // очищаємо поля після успішного збереження (за бажанням)
        $durationMin = $caloriesBurned = $date = ''; 
        // type не очищаємо, щоб localStorage зміг працювати коректно
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична робота 2 - Фітнес-трекер</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 20px auto; line-height: 1.6; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="number"], input[type="date"], select { width: 100%; padding: 8px; box-sizing: border-box; }
        .error { color: red; font-size: 0.9em; display: block; margin-top: 5px; }
        .success { color: green; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; margin-bottom: 20px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <h2>Додати тренування</h2>

    <!-- Вивід повідомлення про успіх -->
    <?php if ($successMessage): ?>
        <div class="success"><?= $successMessage ?></div>
    <?php endif; ?>

    <!-- крок 2 HTML-форма -->
    <!-- значення виводяться через htmlspecialchars() для збереження введених даних при помилці -->
    <form method="post" action="form.php" id="workoutForm">
        
        <div class="form-group">
            <label for="workoutType">Тип тренування:</label>
            <select name="type" id="workoutType" required>
                <option value="">-- Оберіть тип --</option>
                <option value="Біг" <?= $type === 'Біг' ? 'selected' : '' ?>>Біг</option>
                <option value="Йога" <?= $type === 'Йога' ? 'selected' : '' ?>>Йога</option>
                <option value="Силове" <?= $type === 'Силове' ? 'selected' : '' ?>>Силове тренування</option>
                <option value="Плавання" <?= $type === 'Плавання' ? 'selected' : '' ?>>Плавання</option>
            </select>
            <?php if (isset($errors['type'])): ?>
                <span class="error"><?= $errors['type'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="durationMin">Тривалість (у хвилинах, 1-300):</label>
            <!-- HTML5 валідація required, min, max -->
            <input type="number" name="durationMin" id="durationMin" value="<?= htmlspecialchars((string)$durationMin) ?>" required min="1" max="300">
            <?php if (isset($errors['durationMin'])): ?>
                <span class="error"><?= $errors['durationMin'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="caloriesBurned">Спалені калорії:</label>
            <input type="number" name="caloriesBurned" id="caloriesBurned" value="<?= htmlspecialchars((string)$caloriesBurned) ?>" min="0">
            <?php if (isset($errors['caloriesBurned'])): ?>
                <span class="error"><?= $errors['caloriesBurned'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="workoutDate">Дата тренування:</label>
            <!-- HTML5 валідація required, max (не пізніше сьогодні) -->
            <input type="date" name="date" id="workoutDate" value="<?= htmlspecialchars((string)$date) ?>" required max="<?= date('Y-m-d') ?>">
            <?php if (isset($errors['date'])): ?>
                <span class="error"><?= $errors['date'] ?></span>
            <?php endif; ?>
        </div>

        <button type="submit">Зберегти</button>
    </form>

    <script>
        // крок 6 логіка localStorage
        const typeSelect = document.getElementById('workoutType');

        // відновлення значення з localStorage при завантаженні сторінки
        // (лише якщо поле зараз порожнє, щоб не перебивати дані, надіслані через POST)
        const savedType = localStorage.getItem('last_workout_type');
        if (!typeSelect.value && savedType) {
            typeSelect.value = savedType;
        }

        // збереження обраного типу в localStorage при зміні
        typeSelect.addEventListener('change', (event) => {
            localStorage.setItem('last_workout_type', event.target.value);
        });

        // крок 5 клієнтська JavaScript-валідація
        const form = document.getElementById('workoutForm');
        const durationInput = document.getElementById('durationMin');
        const dateInput = document.getElementById('workoutDate');

        form.addEventListener('submit', (event) => {
            let hasError = false;
            
            //  перевірка тривалості
            const duration = parseInt(durationInput.value, 10);
            if (duration < 1 || duration > 300) {
                alert('JS Валідація: Тривалість має бути від 1 до 300 хвилин.');
                hasError = true;
            }

            // перевірка дати (не в майбутньому)
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Обнуляємо час для коректного порівняння дат
            
            if (selectedDate > today) {
                alert('JS Валідація: Дата не може бути пізнішою за сьогодні.');
                hasError = true;
            }

            // якщо є помилка, зупиняємо відправку форми на сервер
            if (hasError) {
                event.preventDefault(); 
            }
        });
    </script>
</body>
</html>