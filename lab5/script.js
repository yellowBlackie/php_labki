const results = document.getElementById('results');
const searchInput = document.getElementById('search');
const totalCaloriesElement = document.getElementById('totalCalories');
const messageElement = document.getElementById('message');

async function loadWorkouts(search = '') {
    try {
        const url =
            'api_list.php?q=' +
            encodeURIComponent(search);

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Помилка сервера');
        }

        const workouts = await response.json();

        renderWorkouts(workouts);

    } catch (error) {
        results.innerHTML = `
            <tr>
                <td colspan="5">
                    Не вдалося завантажити тренування.
                </td>
            </tr>
        `;

        totalCaloriesElement.textContent = '0';

        showMessage(
            'Помилка завантаження даних.',
            'error'
        );
    }
}

function renderWorkouts(workouts) {
    results.innerHTML = '';

    if (workouts.length === 0) {
        results.innerHTML = `
            <tr>
                <td colspan="5">
                    Тренувань не знайдено.
                </td>
            </tr>
        `;

        totalCaloriesElement.textContent = '0';

        return;
    }

    workouts.forEach(workout => {

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${escapeHtml(workout.id)}</td>
            <td>${escapeHtml(workout.type)}</td>
            <td>${escapeHtml(workout.duration_min)}</td>
            <td>${escapeHtml(workout.calories_burned)}</td>
            <td>${escapeHtml(workout.workout_date)}</td>
        `;

        results.appendChild(row);
    });

}

function escapeHtml(value) {
    const div = document.createElement('div');

    div.textContent = String(value);

    return div.innerHTML;
}

async function loadStats() {
    try {
        const response = await fetch('api_stats.php');

        if (!response.ok) {
            throw new Error('Помилка сервера');
        }

        const data = await response.json();

        totalCaloriesElement.textContent = data.total_calories;

    } catch (error) {
        totalCaloriesElement.textContent = '—';

        showMessage(
            'Не вдалося завантажити суму калорій.',
            'error'
        );
    }
}

function showMessage(text, type = '') {
    messageElement.textContent = text;
    messageElement.className = `message ${type}`;
}

searchInput.addEventListener('input', () => {
    loadWorkouts(searchInput.value);
});

const addWorkoutForm =
    document.getElementById('addWorkoutForm');

addWorkoutForm.addEventListener(
    'submit',
    async (event) => {

        event.preventDefault();

        showMessage('');

        const formData =
            new FormData(addWorkoutForm);

        try {
            const response = await fetch(
                'api_add.php',
                {
                    method: 'POST',
                    body: formData
                }
            );

            const data =
                await response.json();

            if (!response.ok) {
                throw new Error(
                    data.error ||
                    'Помилка додавання'
                );
            }

            showMessage(
                'Тренування успішно додано.',
                'success'
            );

            addWorkoutForm.reset();

            await loadWorkouts(
                searchInput.value
            );

            await loadStats();

        } catch (error) {
            showMessage(
                error.message,
                'error'
            );
        }
    }
);

loadWorkouts();
loadStats();

