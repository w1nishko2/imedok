#!/bin/bash
# Скрипт для запуска Laravel Scheduler на Linux хостинге
# Используется в crontab

# Переходим в директорию проекта
cd /path/to/your/project || exit

# Запускаем планировщик Laravel
php artisan schedule:run >> /dev/null 2>&1
