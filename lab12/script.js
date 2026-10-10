const API_URL =
    '../lab8/api.php?resource=workouts';

const results =
    document.getElementById('results');

const searchInput =
    document.getElementById('search');

const totalCaloriesElement =
    document.getElementById('totalCalories');

const workoutCountElement =
    document.getElementById('workoutCount');

const totalDurationElement =
    document.getElementById('totalDuration');

const bestDayElement =
    document.getElementById('bestDay');

const messageElement =
    document.getElementById('message');

const addWorkoutForm =
    document.getElementById('addWorkoutForm');

const canvas =
    document.getElementById('workoutChart');

const ctx =
    canvas.getContext('2d');

const animateBtn =
    document.getElementById('animateBtn');

const stopBtn =
    document.getElementById('stopBtn');

const chartInfo =
    document.getElementById('chartInfo');

const days = [
    'Пн',
    'Вт',
    'Ср',
    'Чт',
    'Пт',
    'Сб',
    'Нд'
];

let allWorkouts = [];
let weeklyData =
    new Array(7).fill(0);

let animationId = null;
let animationStart = null;
let currentProgress = 1;
let barAreas = [];


// Завантажуємо всі тренування з API.
async function loadAllWorkouts() {
    try {
        const response =
            await fetch(API_URL);

        const result =
            await response.json();

        if (
            !response.ok ||
            !result.success
        ) {
            throw new Error(
                result.error ||
                'Не вдалося завантажити тренування.'
            );
        }

        allWorkouts =
            result.data.workouts;

        localStorage.setItem(
            'lab12_workouts',
            JSON.stringify(allWorkouts)
        );

        renderCurrentView();
        updateDashboard();
        prepareChartData();
        startAnimation();

    } catch (error) {
        console.error(error);

        const saved =
            localStorage.getItem(
                'lab12_workouts'
            );

        if (saved) {
            try {
                allWorkouts =
                    JSON.parse(saved);

                renderCurrentView();
                updateDashboard();
                prepareChartData();
                startAnimation();

                showMessage(
                    'API недоступний. Використано останні дані з localStorage.',
                    'error'
                );

                return;
            } catch {
                // Якщо localStorage пошкоджений,
                // нижче покажемо звичайну помилку.
            }
        }

        showMessage(
            'Не вдалося завантажити тренування.',
            'error'
        );
    }
}


// Фільтруємо таблицю за типом.
function renderCurrentView() {
    const search =
        searchInput.value
            .trim()
            .toLowerCase();

    const filtered =
        allWorkouts.filter(
            workout =>
                String(workout.type)
                    .toLowerCase()
                    .includes(search)
        );

    renderWorkouts(filtered);
}


// Виводимо таблицю без innerHTML для даних користувача.
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

        const values = [
            workout.id,
            workout.type,
            workout.duration_min,
            workout.calories_burned,
            workout.workout_date
        ];

        values.forEach(value => {
            const cell =
                document.createElement('td');

            cell.textContent =
                String(value);

            row.appendChild(cell);
        });

        results.appendChild(row);
    });
}


// Оновлюємо загальну статистику.
function updateDashboard() {
    workoutCountElement.textContent =
        String(allWorkouts.length);

    const totalDuration =
        allWorkouts.reduce(
            (sum, workout) =>
                sum +
                Number(workout.duration_min || 0),
            0
        );

    const totalCalories =
        allWorkouts.reduce(
            (sum, workout) =>
                sum +
                Number(workout.calories_burned || 0),
            0
        );

    totalDurationElement.textContent =
        String(totalDuration);

    totalCaloriesElement.textContent =
        String(totalCalories);
}


// Готуємо дані для Canvas.
function prepareChartData() {
    weeklyData =
        new Array(7).fill(0);

    allWorkouts.forEach(workout => {
        const duration =
            Number(workout.duration_min);

        const date =
            new Date(
                `${workout.workout_date}T00:00:00`
            );

        if (
            Number.isNaN(duration) ||
            Number.isNaN(date.getTime())
        ) {
            return;
        }

        const dayIndex =
            (date.getDay() + 6) % 7;

        weeklyData[dayIndex] +=
            duration;
    });

    updateBestDay();
}


// Визначаємо найактивніший день.
function updateBestDay() {
    const maxValue =
        Math.max(...weeklyData);

    if (maxValue === 0) {
        bestDayElement.textContent =
            '—';

        return;
    }

    const index =
        weeklyData.indexOf(maxValue);

    bestDayElement.textContent =
        `${days[index]} (${maxValue} хв)`;
}


// Запускаємо анімацію графіка.
function startAnimation() {
    stopAnimation();

    animationStart = null;
    currentProgress = 0;

    animationId =
        requestAnimationFrame(animate);
}


// Один кадр Canvas-анімації.
function animate(timestamp) {
    if (animationStart === null) {
        animationStart = timestamp;
    }

    const duration = 800;

    currentProgress =
        Math.min(
            (timestamp - animationStart)
            / duration,
            1
        );

    drawChart(currentProgress);

    if (currentProgress < 1) {
        animationId =
            requestAnimationFrame(animate);
    } else {
        animationId = null;
    }
}


// Зупиняємо requestAnimationFrame.
function stopAnimation() {
    if (animationId !== null) {
        cancelAnimationFrame(
            animationId
        );

        animationId = null;
    }
}


// Малюємо Canvas.
function drawChart(progress) {
    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    const left = 60;
    const right = 25;
    const top = 30;
    const bottom = 55;

    const chartWidth =
        canvas.width - left - right;

    const chartHeight =
        canvas.height - top - bottom;

    const maxValue =
        Math.max(...weeklyData, 1);

    drawGrid(
        left,
        top,
        chartWidth,
        chartHeight,
        maxValue
    );

    const gap = 18;

    const barWidth =
        (chartWidth - gap * 8) / 7;

    barAreas = [];

    weeklyData.forEach(
        (value, index) => {
            const animatedValue =
                value * progress;

            const barHeight =
                (animatedValue / maxValue)
                * chartHeight;

            const x =
                left +
                gap +
                index * (barWidth + gap);

            const y =
                top +
                chartHeight -
                barHeight;

            ctx.fillStyle =
                '#2563eb';

            ctx.fillRect(
                x,
                y,
                barWidth,
                barHeight
            );

            ctx.fillStyle =
                '#172033';

            ctx.font =
                '14px Arial';

            ctx.textAlign =
                'center';

            ctx.fillText(
                days[index],
                x + barWidth / 2,
                top + chartHeight + 28
            );

            ctx.fillText(
                `${Math.round(animatedValue)} хв`,
                x + barWidth / 2,
                Math.max(y - 8, 18)
            );

            barAreas.push({
                x,
                y,
                width: barWidth,
                height: barHeight,
                day: days[index],
                value
            });
        }
    );
}


// Малюємо горизонтальну сітку.
function drawGrid(
    left,
    top,
    width,
    height,
    maxValue
) {
    ctx.save();

    ctx.strokeStyle =
        '#d7dce5';

    ctx.fillStyle =
        '#687386';

    ctx.font =
        '12px Arial';

    ctx.textAlign =
        'right';

    const lines = 5;

    for (
        let i = 0;
        i <= lines;
        i++
    ) {
        const y =
            top +
            (height / lines) * i;

        ctx.beginPath();

        ctx.moveTo(
            left,
            y
        );

        ctx.lineTo(
            left + width,
            y
        );

        ctx.stroke();

        const value =
            Math.round(
                maxValue -
                (maxValue / lines) * i
            );

        ctx.fillText(
            String(value),
            left - 8,
            y + 4
        );
    }

    ctx.restore();
}


// Пошук у таблиці.
searchInput.addEventListener(
    'input',
    renderCurrentView
);


// Додаємо тренування через API lab8.
addWorkoutForm.addEventListener(
    'submit',
    async event => {
        event.preventDefault();

        showMessage('');

        const formData =
            new FormData(
                addWorkoutForm
            );

        try {
            const response =
                await fetch(
                    API_URL,
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
                'Тренування успішно додано. Графік оновлено.',
                'success'
            );

            addWorkoutForm.reset();
            searchInput.value = '';

            // Після додавання одразу отримуємо
            // свіжі дані та перебудовуємо Canvas.
            await loadAllWorkouts();

        } catch (error) {
            console.error(error);

            showMessage(
                error.message,
                'error'
            );
        }
    }
);


// Повторний запуск анімації.
animateBtn.addEventListener(
    'click',
    startAnimation
);


// Зупинка анімації.
stopBtn.addEventListener(
    'click',
    stopAnimation
);


// Підказка при наведенні на стовпчик.
canvas.addEventListener(
    'mousemove',
    event => {
        const rect =
            canvas.getBoundingClientRect();

        const scaleX =
            canvas.width / rect.width;

        const scaleY =
            canvas.height / rect.height;

        const x =
            (event.clientX - rect.left)
            * scaleX;

        const y =
            (event.clientY - rect.top)
            * scaleY;

        const bar =
            barAreas.find(
                area =>
                    x >= area.x &&
                    x <=
                        area.x +
                        area.width &&
                    y >= area.y &&
                    y <=
                        area.y +
                        area.height
            );

        if (bar) {
            chartInfo.textContent =
                `${bar.day}: ${bar.value} хв тренувань`;
        } else {
            chartInfo.textContent =
                'Наведіть курсор на стовпчик, щоб побачити значення.';
        }
    }
);


canvas.addEventListener(
    'mouseleave',
    () => {
        chartInfo.textContent =
            'Наведіть курсор на стовпчик, щоб побачити значення.';
    }
);


// При виході зі сторінки
// коректно завершуємо анімацію.
window.addEventListener(
    'beforeunload',
    stopAnimation
);


function showMessage(
    text,
    type = ''
) {
    messageElement.textContent =
        text;

    messageElement.className =
        `message ${type}`;
}


// Початкове завантаження.
loadAllWorkouts();
