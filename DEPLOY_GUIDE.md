# Инструкция по деплою на хостинг Shared Web

## 🚨 Решение проблемы 403 на сервере

### Шаг 1: Обновите код на сервере

```bash
cd /home/g/gamechann2/im-edok_ru
git pull origin main
```

### Шаг 2: Проверьте наличие файлов

```bash
ls -la | grep -E "(index.html|.htaccess)"
ls -la public/ | grep -E "(index.php|.htaccess|test.php)"
```

Должны быть:
- ✅ `.htaccess` (в корне)
- ✅ `index.html` (в корне, fallback)
- ✅ `public/.htaccess`
- ✅ `public/index.php`
- ✅ `public/test.php`

### Шаг 3: Установите правильные права доступа

```bash
# Корневые файлы
chmod 644 .htaccess
chmod 644 index.html

# Public директория
chmod 755 public/
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 644 public/test.php

# Storage и bootstrap/cache
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### Шаг 4: Очистите кэш Laravel

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Шаг 5: Проверьте переменные окружения

```bash
cat .env | grep -E "(APP_ENV|APP_DEBUG|APP_URL)"
```

Должно быть:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://im-edok.ru/
```

### Шаг 6: Проверьте работу сайта

1. **Тест PHP:** https://im-edok.ru/test.php
   - Должен показать информацию о PHP

2. **Прямой доступ:** https://im-edok.ru/public/
   - Должен показать главную страницу

3. **Основной домен:** https://im-edok.ru/
   - Должен автоматически редиректить на Laravel

## 🔧 Если проблема сохраняется

### Вариант 1: Создать файлы вручную на сервере

Если `git pull` не помогает, создайте файлы вручную:

**Файл: `/home/g/gamechann2/im-edok_ru/.htaccess`**
```apache
# Redirect all requests to public folder
<IfModule mod_rewrite.c>
    RewriteEngine on
    AddDefaultCharset UTF-8
    
    # If request is not for public directory
    RewriteCond %{REQUEST_URI} !^/public/
    
    # And not for existing files in root
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Redirect to public folder
    RewriteRule ^(.*)$ public/$1 [L,QSA]
</IfModule>

# If mod_rewrite is not available, show index.php from public
<IfModule !mod_rewrite.c>
    # Allow direct access to public folder
    DirectoryIndex public/index.php index.php
</IfModule>

# Security: Prevent directory listing
Options -Indexes

# Security: Disable access to .env and other sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Allow .well-known folder (for security.txt, etc.)
<DirectoryMatch "\.well-known">
    Order allow,deny
    Allow from all
</DirectoryMatch>
```

**Файл: `/home/g/gamechann2/im-edok_ru/index.html`**
```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=/public/">
    <title>Перенаправление...</title>
</head>
<body>
    <p>Перенаправление на <a href="/public/">главную страницу</a>...</p>
    <script>window.location.href = '/public/';</script>
</body>
</html>
```

**Файл: `/home/g/gamechann2/im-edok_ru/public/test.php`**
```php
<?php
echo "PHP works! Laravel DocumentRoot: " . __DIR__;
phpinfo();
```

### Вариант 2: Обратиться в поддержку хостинга

Попросите изменить настройки Apache для домена `im-edok.ru`:

```
Здравствуйте!

Прошу изменить DocumentRoot для домена im-edok.ru с:
/home/g/gamechann2/im-edok_ru

на:
/home/g/gamechann2/im-edok_ru/public

Это необходимо для корректной работы Laravel-приложения.

Также прошу убедиться, что включены модули:
- mod_rewrite
- mod_deflate
- mod_expires
- mod_headers

И разрешена директива AllowOverride All для моей директории.

Спасибо!
```

## 📊 Диагностика

### Проверка mod_rewrite

```bash
php -r "echo (extension_loaded('mod_rewrite') ? 'Enabled' : 'Disabled');"
```

### Проверка логов Apache

```bash
tail -f /var/log/apache2/error.log
# или
tail -f ~/logs/error.log
```

### Проверка структуры файлов

```bash
tree -L 2 -a
# или
find . -maxdepth 2 -type f -name "*.php" -o -name ".htaccess"
```

## ✅ После успешного деплоя

1. Удалите тестовый файл:
   ```bash
   rm public/test.php
   ```

2. Настройте регулярное обновление:
   ```bash
   # Создайте скрипт deploy.sh
   nano deploy.sh
   ```
   
   Содержимое:
   ```bash
   #!/bin/bash
   cd /home/g/gamechann2/im-edok_ru
   git pull origin main
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   chmod -R 775 storage bootstrap/cache
   ```
   
   Сделайте исполняемым:
   ```bash
   chmod +x deploy.sh
   ```

3. Настройте SSL (если еще не настроен):
   - Через панель управления хостингом
   - Или через Let's Encrypt (если доступно)

## 🎯 Проверка после деплоя

- [ ] Сайт открывается по https://im-edok.ru/
- [ ] Нет ошибки 403
- [ ] Работает поиск рецептов
- [ ] Открываются страницы рецептов
- [ ] Работает пагинация
- [ ] Работает sitemap.xml
- [ ] Работают RSS фиды (/rss, /atom)
- [ ] Яндекс Метрика работает
