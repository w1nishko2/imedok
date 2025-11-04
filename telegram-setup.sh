#!/bin/bash

# ============================================
# Скрипт установки Telegram автопубликации
# для im-edok.ru
# ============================================

echo "🤖 Установка Telegram Bot автопубликации..."
echo ""

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Шаг 1: Проверка наличия .env файла
echo -e "${YELLOW}Шаг 1: Проверка .env файла...${NC}"
if [ ! -f .env ]; then
    echo -e "${RED}❌ Файл .env не найден!${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Файл .env найден${NC}"
echo ""

# Шаг 2: Проверка наличия токена и ID канала в .env
echo -e "${YELLOW}Шаг 2: Проверка настроек Telegram...${NC}"
if grep -q "TELEGRAM_BOT_TOKEN" .env && grep -q "TELEGRAM_CHANNEL_ID" .env; then
    echo -e "${GREEN}✅ Настройки Telegram найдены в .env${NC}"
else
    echo -e "${RED}❌ Настройки Telegram отсутствуют в .env${NC}"
    echo "Добавьте следующие строки в .env:"
    echo "TELEGRAM_BOT_TOKEN=8164470917:AAF3hwmArQu3Q3yb-v4Rs38wgFMfabM9vLE"
    echo "TELEGRAM_CHANNEL_ID=-1002660066518"
    exit 1
fi
echo ""

# Шаг 3: Установка зависимостей
echo -e "${YELLOW}Шаг 3: Проверка composer зависимостей...${NC}"
if ! composer show telegram-bot/api &> /dev/null; then
    echo "Установка telegram-bot/api..."
    composer require telegram-bot/api
else
    echo -e "${GREEN}✅ Пакет telegram-bot/api уже установлен${NC}"
fi
echo ""

# Шаг 4: Запуск миграций
echo -e "${YELLOW}Шаг 4: Запуск миграций базы данных...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Миграции выполнены успешно${NC}"
else
    echo -e "${RED}❌ Ошибка при выполнении миграций${NC}"
    exit 1
fi
echo ""

# Шаг 5: Очистка кэша
echo -e "${YELLOW}Шаг 5: Очистка кэша...${NC}"
php artisan config:clear
php artisan cache:clear
echo -e "${GREEN}✅ Кэш очищен${NC}"
echo ""

# Шаг 6: Тест соединения с ботом
echo -e "${YELLOW}Шаг 6: Тест соединения с Telegram Bot...${NC}"
php artisan telegram:publish-recipe --test
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Соединение с ботом установлено${NC}"
else
    echo -e "${RED}❌ Не удалось подключиться к боту${NC}"
    echo "Проверьте:"
    echo "1. Токен бота в .env"
    echo "2. Интернет-соединение"
    echo "3. Логи: storage/logs/laravel.log"
    exit 1
fi
echo ""

# Шаг 7: Тестовая публикация
echo -e "${YELLOW}Шаг 7: Тестовая публикация рецепта...${NC}"
read -p "Хотите опубликовать тестовый рецепт в канал? (y/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan telegram:publish-recipe
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Рецепт успешно опубликован!${NC}"
        echo "Проверьте канал: https://t.me/imedok_channel"
    else
        echo -e "${RED}❌ Ошибка при публикации${NC}"
    fi
else
    echo "Тестовая публикация пропущена"
fi
echo ""

# Шаг 8: Настройка CRON
echo -e "${YELLOW}Шаг 8: Настройка CRON задачи...${NC}"
echo ""
echo "Для автоматической публикации каждые 10 минут добавьте в crontab:"
echo ""
echo -e "${GREEN}Команда для crontab:${NC}"
echo "*/10 * * * * cd $(pwd) && php artisan telegram:publish-recipe >> storage/logs/telegram-cron.log 2>&1"
echo ""
echo "Инструкция:"
echo "1. Откройте редактор crontab: crontab -e"
echo "2. Добавьте строку выше"
echo "3. Сохраните и выйдите"
echo ""
echo "Или используйте панель управления хостингом (ISPmanager, cPanel, Plesk)"
echo ""

# Финальное сообщение
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Установка завершена успешно!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Что дальше:"
echo "1. Настройте CRON для автопубликации"
echo "2. Проверьте канал: https://t.me/imedok_channel"
echo "3. Просмотрите логи: tail -f storage/logs/telegram-cron.log"
echo "4. Документация: cat TELEGRAM_BOT_GUIDE.md"
echo ""
echo "Полезные команды:"
echo "  php artisan telegram:publish-recipe              - опубликовать рецепт"
echo "  php artisan telegram:publish-recipe --test       - тест соединения"
echo "  php artisan telegram:publish-recipe --recipe-id=5 - конкретный рецепт"
echo ""
echo -e "${GREEN}Готово! 🎉${NC}"
