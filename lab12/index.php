<?php

session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
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
        Фітнес-трекер — Практична робота №12
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >
</head>

<body>

<main class="page">

    <header class="header">

        <p class="eyebrow">
            Практична робота №12 · Варіант 19
        </p>

        <h1>
            Фітнес-трекер
        </h1>

        <p class="subtitle">
            Робота з тренуваннями та Canvas-аналітика
            тривалості за днями тижня.
        </p>

    </header>


    <section class="panel">

        <h2>
            Пошук тренувань
        </h2>

        <label for="search">
            Тип тренування:
        </label>

        <input
            type="text"
            id="search"
            placeholder="Наприклад: Біг"
            autocomplete="off"
        >

    </section>


    <section class="summary">

        <div class="summary-card">

            <span>
                Тренувань
            </span>

            <strong id="workoutCount">
                0
            </strong>

        </div>

        <div class="summary-card">

            <span>
                Загальна тривалість
            </span>

            <strong>
                <span id="totalDuration">
                    0
                </span>
                хв
            </strong>

        </div>

        <div class="summary-card">

            <span>
                Спалено калорій
            </span>

            <strong>
                <span id="totalCalories">
                    0
                </span>
                ккал
            </strong>

        </div>

    </section>


    <section class="panel">

        <h2>
            Список тренувань
        </h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Тип</th>
                        <th>Тривалість</th>
                        <th>Калорії</th>
                        <th>Дата</th>
                    </tr>

                </thead>

                <tbody id="results">
                </tbody>

            </table>

        </div>

    </section>


    <section class="panel">

        <h2>
            Додати тренування
        </h2>

        <form id="addWorkoutForm">

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

            <div class="form-grid">

                <div class="form-group">

                    <label for="type">
                        Тип тренування
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
                        Тривалість (хв)
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
                        Спалені калорії
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
                        Дата
                    </label>

                    <input
                        type="date"
                        id="workout_date"
                        name="workout_date"
                        required
                    >

                </div>

            </div>

            <button type="submit">
                Додати тренування
            </button>

        </form>

        <p
            id="message"
            class="message"
        ></p>

    </section>


    <section class="panel">

        <div class="chart-head">

            <div>

                <p class="eyebrow">
                    Canvas
                </p>

                <h2>
                    Тривалість тренувань за днями тижня
                </h2>

                <p class="chart-description">
                    Висота стовпчика показує сумарну
                    тривалість тренувань у цей день.
                </p>

            </div>

            <div class="chart-actions">

                <button
                    id="animateBtn"
                    type="button"
                >
                    Запустити анімацію
                </button>

                <button
                    id="stopBtn"
                    type="button"
                    class="button-secondary"
                >
                    Стоп
                </button>

            </div>

        </div>


        <div class="chart-meta">

            Найактивніший день:

            <strong id="bestDay">
                —
            </strong>

        </div>


        <div class="canvas-wrap">

            <canvas
                id="workoutChart"
                width="860"
                height="420"
                aria-label="Діаграма тривалості тренувань за днями тижня"
            ></canvas>

        </div>

        <p
            id="chartInfo"
            class="chart-info"
        >
            Наведіть курсор на стовпчик,
            щоб побачити значення.
        </p>

    </section>


    <section class="note">

        <strong>
            Практична №12:
        </strong>

        Canvas-діаграма будується за реальними
        даними фітнес-трекера з API попередньої
        практичної роботи. Після додавання нового
        тренування таблиця, статистика і графік
        оновлюються автоматично.

    </section>

</main>

<script src="script.js"></script>

</body>
</html>
