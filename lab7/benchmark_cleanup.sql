-- Видаляє лише тестові записи,
-- створені файлом benchmark_seed.sql.

DELETE FROM workouts
WHERE type LIKE 'Bench %';
