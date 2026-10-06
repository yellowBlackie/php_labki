# Fitness Tracker API

Практична робота №6  
Варіант 4 — Фітнес-трекер

## Опис

API призначений для роботи з тренуваннями фітнес-трекера.

Єдина точка входу:

```text
api.php
```

Ресурс:

```text
resource=workouts
```

Поля тренування:

- `id` — ідентифікатор тренування;
- `type` — тип тренування;
- `duration_min` — тривалість тренування у хвилинах;
- `calories_burned` — кількість спалених калорій;
- `workout_date` — дата тренування.

Усі відповіді API повертаються у форматі JSON.

Успішна відповідь:

```json
{
  "success": true,
  "data": {}
}
```

Відповідь з помилкою:

```json
{
  "success": false,
  "error": "Опис помилки"
}
```

---

## 1. Отримати список усіх тренувань

### Запит

```http
GET /api.php?resource=workouts
```

### Успішна відповідь

HTTP статус:

```text
200 OK
```

Приклад:

```json
{
  "success": true,
  "data": [
    {
      "id": "10",
      "type": "Біг",
      "duration_min": "25",
      "calories_burned": "270",
      "workout_date": "2026-10-03"
    },
    {
      "id": "11",
      "type": "Плавання",
      "duration_min": "40",
      "calories_burned": "350",
      "workout_date": "2026-10-02"
    }
  ]
}
```

---

## 2. Отримати тренування за ID

### Запит

```http
GET /api.php?resource=workouts&id=11
```

### Успішна відповідь

HTTP статус:

```text
200 OK
```

Приклад:

```json
{
  "success": true,
  "data": {
    "id": "11",
    "type": "Плавання",
    "duration_min": "40",
    "calories_burned": "350",
    "workout_date": "2026-10-02"
  }
}
```

### Якщо тренування не знайдено

Наприклад:

```http
GET /api.php?resource=workouts&id=99999
```

HTTP статус:

```text
404 Not Found
```

Відповідь:

```json
{
  "success": false,
  "error": "Тренування не знайдено."
}
```

### Якщо ID некоректний

HTTP статус:

```text
400 Bad Request
```

Приклад відповіді:

```json
{
  "success": false,
  "error": "Некоректний id."
}
```

---

## 3. Створити нове тренування

### Запит

```http
POST /api.php?resource=workouts
```

Обов'язкові поля:

- `type`;
- `duration_min`;
- `calories_burned`;
- `workout_date`.

Приклад даних:

```text
type=Cardio
duration_min=35
calories_burned=280
workout_date=2026-10-06
```

### Успішна відповідь

HTTP статус:

```text
201 Created
```

Приклад:

```json
{
  "success": true,
  "data": {
    "id": "14",
    "type": "Cardio",
    "duration_min": "35",
    "calories_burned": "280",
    "workout_date": "2026-10-06"
  }
}
```

---

## 4. Помилка при створенні тренування

Якщо обов'язкове поле відсутнє або має некоректне значення, API повертає:

```text
400 Bad Request
```

Наприклад, якщо не передано `calories_burned`:

```json
{
  "success": false,
  "error": "Поле calories_burned є обов’язковим."
}
```

Також перевіряються:

- `duration_min` — має бути додатним цілим числом;
- `calories_burned` — має бути невід'ємним цілим числом;
- `workout_date` — має відповідати формату `YYYY-MM-DD`.

---

## 5. Отримати статистику калорій

Доменна дія для варіанта №4:

```text
action=stats
```

### Запит без фільтра

```http
POST /api.php?resource=workouts&action=stats
```

Повертає суму `calories_burned` для всіх тренувань.

HTTP статус:

```text
200 OK
```

Приклад відповіді:

```json
{
  "success": true,
  "data": {
    "filter": null,
    "total_calories": 2590
  }
}
```

Фактичне значення `total_calories` залежить від записів у базі даних.

---

## 6. Статистика з фільтром за типом тренування

У нашій реалізації параметр `type` використовується як поточний фільтр.

### Запит

```http
POST /api.php?resource=workouts&action=stats
```

Передані дані:

```text
type=Біг
```

API виконує сумування калорій тільки для тренувань, тип яких відповідає фільтру.

Приклад відповіді:

```json
{
  "success": true,
  "data": {
    "filter": "Біг",
    "total_calories": 590
  }
}
```

---

## 7. Невідомий ресурс

Наприклад:

```http
GET /api.php?resource=books
```

HTTP статус:

```text
404 Not Found
```

Відповідь:

```json
{
  "success": false,
  "error": "Невідомий ресурс."
}
```

---

## 8. Невідома дія

Наприклад:

```http
POST /api.php?resource=workouts&action=test
```

Відповідь:

```json
{
  "success": false,
  "error": "Невідома дія."
}
```

---

## 9. Непідтримуваний HTTP-метод

Якщо для ресурсу `workouts` використовується метод, який API не підтримує, наприклад `PUT` або `DELETE`, повертається:

```text
405 Method Not Allowed
```

Приклад відповіді:

```json
{
  "success": false,
  "error": "Метод не підтримується."
}
```

---

## HTTP-коди API

| Код | Значення | Використання |
|---|---|---|
| `200` | OK | Успішний GET або виконання статистики |
| `201` | Created | Нове тренування успішно створено |
| `400` | Bad Request | Некоректні або відсутні дані |
| `404` | Not Found | Ресурс або тренування не знайдено |
| `405` | Method Not Allowed | HTTP-метод не підтримується |
| `500` | Internal Server Error | Помилка роботи з базою даних |

---

## Приклади тестування через curl

### Отримати список

```bash
curl.exe -i "http://localhost/labki_php/lab6/api.php?resource=workouts"
```

### Отримати один запис

```bash
curl.exe -i "http://localhost/labki_php/lab6/api.php?resource=workouts&id=11"
```

### Створити тренування

```bash
curl.exe -i -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts" -H "Content-Type: application/x-www-form-urlencoded" --data "type=Cardio&duration_min=35&calories_burned=280&workout_date=2026-10-06"
```

### Отримати статистику

```bash
curl.exe -i -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts&action=stats"
```

### Отримати статистику для типу Біг

```bash
curl.exe -i -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts&action=stats" -H "Content-Type: application/x-www-form-urlencoded" --data-urlencode "type=Біг"
```

### Перевірити непідтримуваний метод

```bash
curl.exe -i -X PUT "http://localhost/labki_php/lab6/api.php?resource=workouts"
```