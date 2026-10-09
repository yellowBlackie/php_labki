<?php

session_start();

// Для навчального прикладу вважаємо,
// що в системі авторизований користувач з id = 1.
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
    
// Для кожної сесії створюємо CSRF-токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Фітнес-трекер — Практична робота №8
    </title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }

        h1 {
            margin-bottom: 25px;
        }

        .search-container,
        .form-container {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 18px;
            background: #4c7df0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        .total-calories {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eeeeee;
        }

        .message {
            margin: 15px 0;
            font-weight: bold;
        }

        .error {
            color: #c62828;
        }

        .success {
            color: #2e7d32;
        }

        .security-info {
            margin-top: 25px;
            padding: 15px;
            background: #f4f4f4;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <h1>Фітнес-трекер</h1>

    <div class="search-container">

        <label for="search">
            Пошук за типом тренування:
        </label>

        <input
            type="text"
            id="search"
            placeholder="Наприклад: Біг"
            autocomplete="off"
        >

    </div>


    <div class="total-calories">

        Сумарно спалено калорій:

        <span id="totalCalories">
            0
        </span>

        ккал

    </div>


    <div
        id="message"
        class="message"
    ></div>


    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Тип</th>
                <th>Тривалість (хв)</th>
                <th>Калорії</th>
                <th>Дата</th>
            </tr>
        </thead>

        <tbody id="results">
        </tbody>

    </table>


    <div class="form-container">

        <h2>
            Додати тренування
        </h2>

        <form id="addWorkoutForm">

            <!--
                CSRF-токен зберігається в сесії
                і автоматично відправляється разом із FormData.
            -->
            <input
                type="hidden"
                id="csrf_token"
                name="csrf_token"
                value="<?=
                    htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>"
            >


            <div class="form-group">

                <label for="type">
                    Тип тренування:
                </label>

                <input
                    type="text"
                    id="type"
                    name="type"
                    maxlength="100"
                    required
                >

            </div>


            <div class="form-group">

                <label for="duration_min">
                    Тривалість (хв):
                </label>

                <input
                    type="number"
                    id="duration_min"
                    name="duration_min"
                    min="1"
                    required
                >

            </div>


            <div class="form-group">

                <label for="calories_burned">
                    Спалені калорії:
                </label>

                <input
                    type="number"
                    id="calories_burned"
                    name="calories_burned"
                    min="0"
                    required
                >

            </div>


            <div class="form-group">

                <label for="workout_date">
                    Дата:
                </label>

                <input
                    type="date"
                    id="workout_date"
                    name="workout_date"
                    required
                >

            </div>


            <button type="submit">
                Додати тренування
            </button>

        </form>

    </div>


    <div class="security-info">

        Практична робота №8:
        перевірка SQL Injection, XSS,
        CSRF та серверної валідації.

    </div>


    <script src="script.js"></script>

</body>

</html>