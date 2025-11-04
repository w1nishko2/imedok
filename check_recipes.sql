-- Проверка состояния базы данных рецептов

-- Общая статистика
SELECT 
    COUNT(*) as total_recipes,
    COUNT(DISTINCT source_url) as unique_urls,
    MIN(created_at) as first_recipe,
    MAX(created_at) as last_recipe
FROM recipes;

-- Последние 10 рецептов
SELECT id, title, source_url, created_at 
FROM recipes 
ORDER BY id DESC 
LIMIT 10;

-- Статистика по датам
SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
FROM recipes
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 7;
