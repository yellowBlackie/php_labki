-- Додаємо власника тренування.
ALTER TABLE workouts
ADD COLUMN owner_id INT NOT NULL DEFAULT 1 AFTER id;

-- Індекс для перевірки належності.
CREATE INDEX idx_workouts_owner
ON workouts (owner_id);

-- Для демонстрації перевірки чужого запису
-- тестовий XSS-запис призначаємо іншому користувачу.
UPDATE workouts
SET owner_id = 2
WHERE id = 15;