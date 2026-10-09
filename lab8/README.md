# Практична робота №8

## Безпека PHP-застосунків: пошук і усунення типових вразливостей

**Варіант 4 — Фітнес-трекер**

У практичній роботі проведено аудит PHP-застосунку, створеного протягом попередніх практичних робіт, та перевірено захист від основних вебвразливостей.

Основна увага приділялася:

- SQL Injection;
- XSS;
- CSRF;
- серверній валідації;
- небезпечним діям через GET;
- редагуванню чужих записів;
- безпечній обробці помилок.

---

## Мета роботи

Провести аудит практичних робіт №1–7, знайти потенційні проблеми безпеки та усунути їх.

Для варіанта №4 необхідно було перевірити:

- SQL Injection через параметр `type`;
- XSS через поле `type`;
- CSRF під час додавання тренування;
- можливість редагування чужого тренування без перевірки власника.

---

## Структура практичної роботи

```text
lab8/
├── .gitignore
├── README.md
├── api.php
├── db.php
├── index.php
├── script.js
├── security_migration.sql
└── tests/
    ├── audit_commands.ps1
    └── security_tests.md
```

### Призначення файлів

- `index.php` — основна сторінка фітнес-трекера та створення CSRF-токена;
- `api.php` — API для отримання, додавання, редагування тренувань та статистики;
- `db.php` — підключення до бази даних;
- `script.js` — AJAX-запити та безпечне відображення даних;
- `security_migration.sql` — додавання поля `owner_id` для перевірки власника тренування;
- `tests/security_tests.md` — опис виконаних тестів безпеки;
- `tests/audit_commands.ps1` — PowerShell-команди для аудиту практичних робіт №4–7.

---

## Запуск

Проєкт запускається через Apache.

Основна сторінка:

```text
http://localhost/labki_php/lab8/index.php
```

API:

```text
http://localhost/labki_php/lab8/api.php?resource=workouts
```

База даних:

```text
practicum4
```

MySQL працює на порту:

```text
3307
```

---

# 1. SQL Injection

Для варіанта №4 потрібно було перевірити параметр фільтрації `type`.

Нормальний запит:

```text
GET /lab8/api.php?resource=workouts&type=Біг
```

У результаті повертаються тільки тренування, які відповідають введеному типу.

Для перевірки SQL Injection використовувався payload:

```text
' OR '1'='1
```

Команда:

```powershell
curl.exe -i -G "http://localhost/labki_php/lab8/api.php" `
  --data-urlencode "resource=workouts" `
  --data-urlencode "type=' OR '1'='1"
```

У результаті сервер повернув:

```text
HTTP 200
workouts: []
```

SQL Injection не виконується, оскільки введений рядок обробляється як звичайне значення.

Для захисту використовується prepared statement:

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

Таким чином значення користувача не додається безпосередньо до SQL-запиту.

---

# 2. XSS

Для перевірки XSS у поле `type` було введено:

```html
<script>alert(1)</script>
```

Запис було збережено в базу даних.

Після повторного завантаження сторінки код JavaScript не виконався.

У таблиці було показано звичайний текст:

```text
<script>alert(1)</script>
```

Для виводу даних використовується `textContent`:

```javascript
typeCell.textContent =
    String(workout.type);
```

Також інші значення таблиці виводяться через `textContent`.

Наприклад:

```javascript
idCell.textContent =
    String(workout.id);

durationCell.textContent =
    String(workout.duration_min);

caloriesCell.textContent =
    String(workout.calories_burned);

dateCell.textContent =
    String(workout.workout_date);
```

На відміну від `innerHTML`, `textContent` не виконує HTML або JavaScript, введений користувачем.

---

# 3. CSRF

Для всіх POST-запитів, які змінюють дані або виконують доменну дію, реалізовано CSRF-захист.

При відкритті `index.php` створюється токен:

```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}
```

Токен додається у форму:

```php
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
```

На сервері токен перевіряється функцією:

```php
function verifyCsrfToken(array $data): void
{
    $sessionToken =
        $_SESSION['csrf_token'] ?? '';

    $requestToken =
        $data['csrf_token'] ?? '';

    if (
        $sessionToken === '' ||
        $requestToken === '' ||
        !hash_equals(
            $sessionToken,
            $requestToken
        )
    ) {
        sendError(
            'Недійсний CSRF-токен.',
            403
        );
    }
}
```

---

## Перевірка POST без CSRF-токена

Команда:

```powershell
curl.exe -i -X POST "http://localhost/labki_php/lab8/api.php?resource=workouts" `
  --data "type=Test&duration_min=10&calories_burned=100&workout_date=2026-10-09"
```

Результат:

```text
HTTP/1.1 403 Forbidden
```

JSON:

```json
{
  "success": false,
  "error": "Недійсний CSRF-токен."
}
```

Отже, POST-запит без токена не виконується.

---

## Перевірка правильного CSRF-токена

Для отримання сесії:

```powershell
curl.exe -s -c lab8_cookie.txt "http://localhost/labki_php/lab8/index.php" -o lab8_index.html
```

Отримання токена:

```powershell
$html = Get-Content .\lab8_index.html -Raw

$csrf = [regex]::Match(
    $html,
    'name="csrf_token"[\s\S]*?value="([^"]+)"'
).Groups[1].Value
```

Перевірка `action=stats`:

```powershell
curl.exe -i -b lab8_cookie.txt `
  -X POST "http://localhost/labki_php/lab8/api.php?resource=workouts&action=stats" `
  --data-urlencode "csrf_token=$csrf"
```

Результат:

```text
HTTP/1.1 200 OK
```

Приклад відповіді:

```json
{
  "success": true,
  "data": {
    "filter": null,
    "total_calories": 71430,
    "from_cache": false
  }
}
```

Після очищення тестових benchmark-даних загальна кількість калорій відповідно змінюється.

---

# 4. Серверна валідація

HTML-атрибути `required`, `min` та `maxlength` не вважаються достатнім захистом, оскільки їх можна обійти прямим HTTP-запитом.

Тому всі основні поля додатково перевіряються у PHP.

Перевіряються:

- `type`;
- довжина `type`;
- `duration_min`;
- `calories_burned`;
- `workout_date`.

Приклад перевірки типу:

```php
if ($type === '') {
    sendError(
        'Поле type є обов’язковим.',
        400
    );
}
```

Перевірка довжини:

```php
if (mb_strlen($type) > 100) {
    sendError(
        'Поле type є занадто довгим.',
        400
    );
}
```

Перевірка тривалості:

```php
if (
    filter_var(
        $duration,
        FILTER_VALIDATE_INT
    ) === false ||
    (int)$duration <= 0
) {
    sendError(
        'duration_min має бути додатним цілим числом.',
        400
    );
}
```

Перевірка калорій:

```php
if (
    filter_var(
        $calories,
        FILTER_VALIDATE_INT
    ) === false ||
    (int)$calories < 0
) {
    sendError(
        'calories_burned має бути невід’ємним цілим числом.',
        400
    );
}
```

Перевірка дати:

```php
$dateObject =
    DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $date
) {
    sendError(
        'workout_date має бути у форматі YYYY-MM-DD.',
        400
    );
}
```

---

## Результати перевірки

POST без `calories_burned`:

```text
400 Bad Request
Поле calories_burned є обов’язковим.
```

POST із:

```text
duration_min=-5
```

повертає:

```text
400 Bad Request
duration_min має бути додатним цілим числом.
```

Некоректні дані не записуються в базу.

---

# 5. Заборона зміни даних через GET

Дії, які змінюють дані, не дозволяється виконувати GET-запитом.

У `api.php` реалізовано:

```php
if ($method === 'GET') {

    if ($action !== null) {
        sendError(
            'Дія доступна лише через POST.',
            405
        );
    }

    if ($id === null) {
        listWorkouts($pdo);
    } else {
        getWorkout($pdo, $id);
    }
}
```

Перевірка:

```powershell
curl.exe -i "http://localhost/labki_php/lab8/api.php?resource=workouts&action=update&id=15"
```

Результат:

```text
HTTP/1.1 405 Method Not Allowed
```

JSON:

```json
{
  "success": false,
  "error": "Дія доступна лише через POST."
}
```

---

# 6. Перевірка власника тренування

За умовою варіанта потрібно було усунути можливість редагування чужого тренування.

Для цього до таблиці `workouts` додається поле:

```text
owner_id
```

Міграція знаходиться у файлі:

```text
security_migration.sql
```

Вміст:

```sql
ALTER TABLE workouts
ADD COLUMN owner_id INT NOT NULL DEFAULT 1 AFTER id;

CREATE INDEX idx_workouts_owner
ON workouts (owner_id);

UPDATE workouts
SET owner_id = 2
WHERE id = 15;
```

Для навчального прикладу поточний користувач має:

```text
user_id = 1
```

У `index.php` та `api.php` використовується:

```php
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
```

Перед редагуванням API отримує власника тренування:

```php
$stmt = $pdo->prepare(
    'SELECT id, owner_id
     FROM workouts
     WHERE id = :id'
);

$stmt->execute([
    ':id' => (int)$id
]);
```

Після цього виконується перевірка:

```php
if (
    (int)$workout['owner_id']
    !== $ownerId
) {
    sendError(
        'Немає прав на редагування цього тренування.',
        403
    );
}
```

Сам SQL UPDATE також додатково перевіряє власника:

```php
$stmt = $pdo->prepare(
    'UPDATE workouts
     SET type = :type,
         duration_min = :duration,
         calories_burned = :calories,
         workout_date = :date
     WHERE id = :id
       AND owner_id = :owner_id'
);
```

---

## Результат перевірки чужого тренування

Для тестового запису:

```text
id = 15
owner_id = 2
```

а поточний користувач:

```text
user_id = 1
```

Спроба редагування повернула:

```text
403 Forbidden
```

Повідомлення:

```json
{
  "success": false,
  "error": "Немає прав на редагування цього тренування."
}
```

Редагування власного тренування повертає:

```text
200 OK
```

---

# 7. Безпечна обробка помилок

Користувачу не повинні показуватися:

- `PDOException`;
- `SQLSTATE`;
- пароль до БД;
- локальні шляхи до файлів;
- повний текст технічної помилки.

У `api.php` вимкнено показ сирих PHP-помилок:

```php
ini_set('display_errors', '0');
```

Використовується:

```php
catch (Throwable $e) {

    error_log(
        'Lab8 error: ' .
        $e->getMessage()
    );

    sendError(
        'Внутрішня помилка сервера.',
        500
    );
}
```

У `db.php` також технічна помилка записується в лог:

```php
catch (PDOException $e) {

    error_log(
        'Lab8 database connection error: ' .
        $e->getMessage()
    );

    throw new RuntimeException(
        'Database connection failed.'
    );
}
```

При вимкненій MySQL API повертає:

```text
500 Internal Server Error
```

Відповідь:

```json
{
  "success": false,
  "error": "Внутрішня помилка сервера."
}
```

При цьому користувач не бачить `PDOException`, `SQLSTATE` або шляхи до локальних файлів.

---

# 8. Аудит практичних робіт №4–7

Для перевірки попередніх практичних робіт створено:

```text
tests/audit_commands.ps1
```

Скрипт перевіряє:

- використання `$_GET` та `$_POST`;
- можливу конкатенацію значень користувача в SQL;
- наявність CSRF-захисту;
- потенційно небезпечний HTML-вивід;
- видалення через GET.

Запуск:

```powershell
powershell -ExecutionPolicy Bypass -File .\lab8\tests\audit_commands.ps1
```

Під час аудиту підозрілої прямої конкатенації `$_GET` або `$_POST` у SQL-запити не знайдено.

Для практичних робіт №4–7 було перевірено та виправлено CSRF-захист.

---

## Практична робота №4

Додано:

```text
lab4/csrf.php
```

CSRF використовується для:

- додавання;
- редагування;
- видалення.

Видалення більше не виконується через GET.

Перевірка GET:

```powershell
curl.exe -i "http://localhost/labki_php/lab4/delete.php?id=14"
```

Результат:

```text
405 Method Not Allowed
```

POST без CSRF:

```powershell
curl.exe -i -X POST "http://localhost/labki_php/lab4/delete.php" --data "id=14"
```

Результат:

```text
403 Forbidden
```

---

## Практична робота №5

Додано CSRF-захист форми AJAX.

POST без токена повертає:

```text
403 Forbidden
```

Для XSS дані в таблиці виводяться через:

```javascript
textContent
```

---

## Практична робота №6

CSRF додано до:

- створення тренування;
- `action=stats`.

POST без токена повертає:

```text
403 Forbidden
```

Запит із правильним токеном успішно повертає статистику та дозволяє створювати записи.

---

## Практична робота №7

CSRF додано до POST-операцій API.

Також у практичній роботі №7 збережені окремі файли для підтвердження оптимізації:

```text
before_nplus1.php
create_index.sql
benchmark_seed.sql
benchmark_cleanup.sql
benchmark_results.md
```

---

# 9. Порівняння «до / після»

| Перевірка | До аудиту | Після аудиту |
|---|---|---|
| SQL Injection | потрібно було повторно перевірити запити та фільтри | дані передаються через prepared statements |
| XSS | користувацькі дані могли бути небезпечними при неправильному HTML-виводі | використовується `textContent` та `htmlspecialchars()` |
| CSRF | у частині старих POST-форм токена не було | POST-операції перевіряють CSRF-токен |
| Видалення через GET | було присутнє у старій CRUD-реалізації | використовується POST + CSRF |
| Серверна валідація | частина перевірок виконувалася тільки HTML-формою | дані повторно перевіряються у PHP |
| Редагування чужого запису | перевірки власника не було | перевіряється `owner_id` |
| Помилки БД | могла бути показана зайва технічна інформація | користувач бачить загальне повідомлення, деталі йдуть у `error_log()` |

---

# 10. Фінальна перевірка всіх практичних робіт

Після внесення змін було перевірено запуск усіх практичних робіт.

Перевірялися адреси:

```text
http://localhost/labki_php/lab1/index.php
http://localhost/labki_php/lab2/form.php
http://localhost/labki_php/lab3/index.php
http://localhost/labki_php/lab4/index.php
http://localhost/labki_php/lab5/index.php
http://localhost/labki_php/lab6/api.php?resource=workouts
http://localhost/labki_php/lab7/api.php?resource=workouts
http://localhost/labki_php/lab8/index.php
```

Результат:

```text
200  lab1
200  lab2
200  lab3
200  lab4
200  lab5
200  lab6
200  lab7
200  lab8
```

Усі практичні роботи запускаються після внесення змін безпеки.

---

# 11. Тестові матеріали

Детальний опис виконаних security-тестів знаходиться у:

```text
tests/security_tests.md
```

У ньому збережені:

- payload SQL Injection;
- payload XSS;
- перевірки CSRF;
- перевірки серверної валідації;
- перевірка GET-дій;
- перевірка `owner_id`;
- перевірка обробки помилок.

---

# Висновок

Під час виконання практичної роботи було проведено аудит безпеки PHP-застосунку та попередніх практичних робіт.

Було перевірено захист від SQL Injection, XSS та CSRF, серверну валідацію введених даних, використання HTTP-методів та обробку помилок.

Для SQL-запитів використовуються підготовлені запити `prepare()` та `execute()`. Для захисту від XSS користувацькі дані виводяться через `textContent` або `htmlspecialchars()`. POST-операції захищені CSRF-токеном із перевіркою через `hash_equals()`.

Також було додано перевірку `owner_id`, завдяки якій користувач не може редагувати чуже тренування. Дії, що змінюють дані, не виконуються через GET-запити.

Після внесення змін усі практичні роботи №1–8 були перевірені та успішно запускаються.
