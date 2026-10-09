# Результати ApacheBench — Практична робота №7

## Умови тесту

Обидва тести виконано з однаковими параметрами:

```text
-n 200 -c 20
```

Для тесту використовувалась база з 300+ записами.

---

## До оптимізації — N+1

Endpoint:

```text
/labki_php/lab7/before_nplus1.php
```

Команда:

```powershell
& "D:\papka1\apache\bin\ab.exe" -l -n 200 -c 20 "http://localhost/labki_php/lab7/before_nplus1.php"
```

Результат:

```text
Time taken for tests:   4.914 seconds
Complete requests:      200
Failed requests:        0
Requests per second:    40.70 [#/sec]
Time per request:       491.367 [ms]
Time per request:       24.568 [ms] (across all concurrent requests)
```

---

## Після оптимізації — GROUP BY + кеш

Endpoint:

```text
/labki_php/lab7/api.php?resource=workouts
```

Команда:

```powershell
& "D:\papka1\apache\bin\ab.exe" -l -n 200 -c 20 "http://localhost/labki_php/lab7/api.php?resource=workouts"
```

Результат:

```text
Time taken for tests:   1.122 seconds
Complete requests:      200
Failed requests:        0
Requests per second:    178.30 [#/sec]
Time per request:       112.168 [ms]
Time per request:       5.608 [ms] (across all concurrent requests)
```

---

## Порівняння

- Requests per second: `40.70 -> 178.30`
- Time per request: `491.367 ms -> 112.168 ms`
- Failed requests: `0 -> 0`
- Пропускна здатність зросла приблизно у `4.38` раза.
- Середній час одного запиту зменшився приблизно на `77.2%`.

Ці значення отримані локально через ApacheBench, а не взяті з теоретичних розрахунків.
