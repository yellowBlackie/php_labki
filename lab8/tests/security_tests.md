# Перевірки безпеки — Практична робота №8

Варіант 4 — Фітнес-трекер.

Нижче зібрані перевірки, які виконувалися для SQL Injection, XSS, CSRF,
серверної валідації, небезпечних GET-дій, перевірки власника та обробки помилок.

## 1. SQL Injection

Перевірявся параметр `type`.

Нормальний запит:

```text
GET /lab8/api.php?resource=workouts&type=Біг
```

Повертаються лише записи, які відповідають фільтру.

Payload:

```text
' OR '1'='1
```

Команда:

```powershell
curl.exe -i -G "http://localhost/labki_php/lab8/api.php" `
  --data-urlencode "resource=workouts" `
  --data-urlencode "type=' OR '1'='1"
```

Фактичний результат:

```text
HTTP 200
workouts: []
```

Ін'єкція не змінює структуру SQL-запиту.

Захист:

```php
$stmt = $pdo->prepare(
    'SELECT id,
            type,
            duration_min,
            calories_burned,
            workout_date
     FROM workouts
     WHERE type LIKE :type
     ORDER BY workout_date DESC, id DESC'
);

$stmt->execute([
    ':type' => '%' . $type . '%'
]);
```

## 2. XSS

У поле `type` було записано:

```html
<script>alert(1)</script>
```

Запис зберігся в БД, але після відкриття таблиці JavaScript не виконався.
Payload відобразився як звичайний текст.

У `script.js` значення користувача виводяться через `textContent`:

```javascript
typeCell.textContent =
    String(workout.type);
```

Тому HTML із поля `type` не інтерпретується браузером.

## 3. CSRF

CSRF-токен створюється у сесії в `index.php`:

```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}
```

У форму він додається прихованим полем.

На сервері використовується `hash_equals()`.

### POST без токена

```powershell
curl.exe -i -X POST "http://localhost/labki_php/lab8/api.php?resource=workouts" `
  --data "type=Test&duration_min=10&calories_burned=100&workout_date=2026-10-09"
```

Результат:

```text
403 Forbidden
{"success":false,"error":"Недійсний CSRF-токен."}
```

### Неправильний токен

POST із cookie сесії, але з неправильним `csrf_token`, також повертає:

```text
403 Forbidden
```

### Правильний токен

Форма `index.php` передає токен через `FormData`.
Додавання тренування через сторінку виконується успішно.

## 4. Серверна валідація

HTML-атрибутів `required` недостатньо, тому дані перевіряються ще раз у PHP.

Перевірка без `calories_burned` повернула:

```text
400 Bad Request
Поле calories_burned є обов’язковим.
```

Перевірка:

```text
duration_min=-5
```

повернула:

```text
400 Bad Request
duration_min має бути додатним цілим числом.
```

Також перевіряються:

- `type`;
- максимальна довжина `type`;
- `duration_min`;
- `calories_burned`;
- формат `workout_date`.

## 5. Дії через GET

Зміна даних через GET заборонена.

Перевірка:

```powershell
curl.exe -i "http://localhost/labki_php/lab8/api.php?resource=workouts&action=update&id=15"
```

Результат:

```text
405 Method Not Allowed
{"success":false,"error":"Дія доступна лише через POST."}
```

## 6. Перевірка належності тренування

Для навчального прикладу поточний користувач має:

```text
user_id = 1
```

У `security_migration.sql` тестовому запису `id=15` призначається:

```text
owner_id = 2
```

Спроба користувача `1` змінити цей запис через
`POST action=update` повернула:

```text
403 Forbidden
{"success":false,"error":"Немає прав на редагування цього тренування."}
```

Для власного запису з `owner_id=1` редагування повернуло:

```text
200 OK
Тренування успішно оновлено.
```

У SQL `UPDATE` додатково перевіряється `owner_id`:

```sql
UPDATE workouts
SET type = :type,
    duration_min = :duration,
    calories_burned = :calories,
    workout_date = :date
WHERE id = :id
  AND owner_id = :owner_id
```

## 7. Обробка помилок

Було перевірено поведінку API при недоступній MySQL.

Користувач отримав тільки:

```text
500 Internal Server Error
{"success":false,"error":"Внутрішня помилка сервера."}
```

У відповіді немає:

```text
PDOException
SQLSTATE
локального шляху до файлів
```

Технічна інформація записується через `error_log()`.

## 8. Аудит попередніх практикумів

Під час аудиту практикумів №4–7 перевірялися використання `$_GET`,
`$_POST`, SQL-запити та POST-форми.

Було перевірено, що значення `$_GET`/`$_POST` не конкатенуються
безпосередньо в SQL-запити.

Окремо були виправлені POST-дії:

- у практикумі №4 додавання, редагування і видалення захищені CSRF;
- видалення в №4 більше не виконується через GET;
- у практикумі №5 додавання тренування захищене CSRF;
- у практикумі №6 створення тренування та `action=stats` захищені CSRF;
- у практикумі №7 створення тренування та `action=stats` захищені CSRF.

## 9. Коротко «до / після»

| Перевірка | До аудиту | Після аудиту |
|---|---|---|
| SQL Injection | потрібно було повторно перевірити фільтри | `type` передається через prepared statement |
| XSS | введені дані могли бути небезпечними при HTML-виводі | `type` виводиться через `textContent` / екранування |
| CSRF | у старих POST-формах токенів не було | POST-дії перевіряють CSRF-токен |
| Видалення через GET | було в практикумі №4 | тільки POST + CSRF + confirm |
| Серверна валідація | частина перевірок була лише у формах | обов'язкові поля та числа перевіряються на сервері |
| Редагування чужого запису | перевірки власника не було | `owner_id` перевіряється до UPDATE |
| Помилки БД | можливий технічний текст помилки | загальна відповідь + `error_log()` |

## Висновок

Після аудиту основні перевірені вразливості закриті:
SQL Injection не виконується, stored XSS не запускається,
POST-дії захищені CSRF-токеном, небезпечні зміни через GET відхиляються,
валідація виконується на сервері, а редагування чужого тренування
блокується перевіркою `owner_id`.
