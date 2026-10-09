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

- `id`
- `type`
- `duration_min`
- `calories_burned`
- `workout_date`

Усі відповіді повертаються у форматі JSON.

---

## 1. Отримати всі тренування

```http
GET /api.php?resource=workouts
```

Успішна відповідь:

```text
200 OK
```

---

## 2. Отримати тренування за ID

```http
GET /api.php?resource=workouts&id=11
```

Якщо запис існує:

```text
200 OK
```

Якщо запис не знайдено:

```text
404 Not Found
```

Якщо `id` некоректний:

```text
400 Bad Request
```

---

## 3. Отримати CSRF-токен

POST-дії захищені CSRF-токеном.

Спочатку потрібно отримати токен і зберегти cookie сесії.

```bash
curl.exe -s -c lab6_cookie.txt "http://localhost/labki_php/lab6/api.php?resource=workouts&action=csrf"
```

Приклад відповіді:

```json
{
  "success": true,
  "data": {
    "csrf_token": "..."
  }
}
```

У PowerShell токен можна отримати так:

```powershell
$csrfResponse = curl.exe -s -b lab6_cookie.txt "http://localhost/labki_php/lab6/api.php?resource=workouts&action=csrf" | ConvertFrom-Json
$csrf = $csrfResponse.data.csrf_token
```

---

## 4. Створити нове тренування

```http
POST /api.php?resource=workouts
```

Обов'язкові поля:

- `csrf_token`
- `type`
- `duration_min`
- `calories_burned`
- `workout_date`

Приклад:

```powershell
curl.exe -i -b lab6_cookie.txt -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts" `
  --data-urlencode "csrf_token=$csrf" `
  --data-urlencode "type=Cardio" `
  --data "duration_min=35&calories_burned=280&workout_date=2026-10-06"
```

Успішна відповідь:

```text
201 Created
```

Без токена або з неправильним токеном:

```text
403 Forbidden
```

---

## 5. Серверна валідація

API перевіряє:

- `type` — обов'язковий рядок;
- `duration_min` — додатне ціле число;
- `calories_burned` — невід'ємне ціле число;
- `workout_date` — формат `YYYY-MM-DD`.

Некоректні дані повертають:

```text
400 Bad Request
```

---

## 6. Доменна дія stats

```http
POST /api.php?resource=workouts&action=stats
```

Дія також потребує правильного CSRF-токена.

Без фільтра:

```powershell
curl.exe -i -b lab6_cookie.txt -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts&action=stats" `
  --data-urlencode "csrf_token=$csrf"
```

З фільтром:

```powershell
curl.exe -i -b lab6_cookie.txt -X POST "http://localhost/labki_php/lab6/api.php?resource=workouts&action=stats" `
  --data-urlencode "csrf_token=$csrf" `
  --data-urlencode "type=Біг"
```

Приклад результату:

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

```http
GET /api.php?resource=books
```

Результат:

```text
404 Not Found
```

---

## 8. Непідтримуваний HTTP-метод

Наприклад:

```bash
curl.exe -i -X PUT "http://localhost/labki_php/lab6/api.php?resource=workouts"
```

Результат:

```text
405 Method Not Allowed
```

---

## 9. Безпечна обробка помилок

Сирі повідомлення `PDOException`, `SQLSTATE`, stack trace та локальні шляхи не повертаються користувачу.

Деталі помилки записуються через:

```php
error_log()
```

Клієнт отримує загальне повідомлення:

```json
{
  "success": false,
  "error": "Внутрішня помилка сервера."
}
```

---

## HTTP-коди API

| Код | Значення | Використання |
|---|---|---|
| `200` | OK | Успішний GET або `stats` |
| `201` | Created | Тренування створено |
| `400` | Bad Request | Некоректні дані |
| `403` | Forbidden | Неправильний CSRF-токен |
| `404` | Not Found | Ресурс або запис не знайдено |
| `405` | Method Not Allowed | Метод не підтримується |
| `500` | Internal Server Error | Внутрішня помилка |
