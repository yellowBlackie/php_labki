const results = document.getElementById('results');
const searchInput = document.getElementById('search');
const totalCaloriesElement = document.getElementById('totalCalories');
const messageElement = document.getElementById('message');
const addWorkoutForm = document.getElementById('addWorkoutForm');


// Завантажує список тренувань.
// Якщо є текст пошуку, передаємо його як фільтр type.
async function loadWorkouts(search = '') {
    try {
        let url = 'api.php?resource=workouts';

        if (search.trim() !== '') {
            url += '&type=' + encodeURIComponent(search);
        }

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(
                'Помилка завантаження тренувань.'
            );
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(
                result.error ||
                'Не вдалося отримати тренування.'
            );
        }

        renderWorkouts(
            result.data.workouts
        );

    } catch (error) {
        console.error(error);

        results.innerHTML = '';

        const row =
            document.createElement('tr');

        const cell =
            document.createElement('td');

        cell.colSpan = 5;

        cell.textContent =
            'Не вдалося завантажити тренування.';

        row.appendChild(cell);
        results.appendChild(row);

        showMessage(
            'Помилка завантаження даних.',
            'error'
        );
    }
}


// Виводить список тренувань у таблицю.
// Для даних користувача використовується textContent,
// тому HTML та JavaScript з поля type не виконуються.
function renderWorkouts(workouts) {
    results.innerHTML = '';

    if (workouts.length === 0) {
        const row =
            document.createElement('tr');

        const cell =
            document.createElement('td');

        cell.colSpan = 5;

        cell.textContent =
            'Тренувань не знайдено.';

        row.appendChild(cell);
        results.appendChild(row);

        return;
    }

    workouts.forEach(workout => {
        const row =
            document.createElement('tr');

        const idCell =
            document.createElement('td');

        const typeCell =
            document.createElement('td');

        const durationCell =
            document.createElement('td');

        const caloriesCell =
            document.createElement('td');

        const dateCell =
            document.createElement('td');


        idCell.textContent =
            String(workout.id);

        typeCell.textContent =
            String(workout.type);

        durationCell.textContent =
            String(workout.duration_min);

        caloriesCell.textContent =
            String(workout.calories_burned);

        dateCell.textContent =
            String(workout.workout_date);


        row.appendChild(idCell);
        row.appendChild(typeCell);
        row.appendChild(durationCell);
        row.appendChild(caloriesCell);
        row.appendChild(dateCell);

        results.appendChild(row);
    });
}


async function loadStats() {
    try {
        const csrfToken =
            document.getElementById(
                'csrf_token'
            ).value;

        const formData =
            new FormData();

        formData.append(
            'csrf_token',
            csrfToken
        );

        const response = await fetch(
            'api.php?resource=workouts&action=stats',
            {
                method: 'POST',
                body: formData
            }
        );

        const result =
            await response.json();

        if (
            !response.ok ||
            !result.success
        ) {
            throw new Error(
                result.error ||
                'Не вдалося отримати статистику.'
            );
        }

        totalCaloriesElement.textContent =
            String(
                result.data.total_calories
            );

    } catch (error) {
        console.error(error);

        totalCaloriesElement.textContent =
            '—';

        showMessage(
            error.message,
            'error'
        );
    }
}


// Показує повідомлення користувачу.
function showMessage(text, type = '') {
    messageElement.textContent = text;
    messageElement.className =
        `message ${type}`;
}


// Живий пошук за типом тренування.
searchInput.addEventListener(
    'input',
    () => {
        showMessage('');

        loadWorkouts(
            searchInput.value
        );
    }
);


// Додавання нового тренування.
addWorkoutForm.addEventListener(
    'submit',
    async (event) => {

        event.preventDefault();

        showMessage('');

        const formData =
            new FormData(addWorkoutForm);

        try {
            const response = await fetch(
                'api.php?resource=workouts',
                {
                    method: 'POST',
                    body: formData
                }
            );

            const result =
                await response.json();

            if (
                !response.ok ||
                !result.success
            ) {
                throw new Error(
                    result.error ||
                    'Не вдалося додати тренування.'
                );
            }

            showMessage(
                'Тренування успішно додано.',
                'success'
            );

            addWorkoutForm.reset();

            searchInput.value = '';

            await loadWorkouts();
            await loadStats();

        } catch (error) {
            console.error(error);

            showMessage(
                error.message,
                'error'
            );
        }
    }
);


// Початкове завантаження сторінки.
loadWorkouts();
loadStats();