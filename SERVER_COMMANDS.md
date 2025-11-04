# Быстрая инструкция для сервера

## ⚡ Выполните на сервере эти команды:

```bash
# 1. Перейдите в директорию проекта
cd /home/g/gamechann2/im-edok_ru

# 2. Обновите код
git pull origin main

# 3. Запустите автоматический деплой
bash deploy.sh
```

## 🔧 Если git pull выдает ошибку:

```bash
# Сбросьте локальные изменения
git reset --hard HEAD
git pull origin main
bash deploy.sh
```

## 🚨 Если файлы все равно отсутствуют:

Создайте их вручную:

### 1. Создайте index.html в корне:
```bash
cat > index.html << 'EOF'
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
EOF
```

### 2. Создайте public/test.php:
```bash
cat > public/test.php << 'EOF'
<?php
echo "PHP works! Laravel DocumentRoot: " . __DIR__;
phpinfo();
EOF
```

### 3. Проверьте права:
```bash
chmod 644 index.html
chmod 644 .htaccess
chmod 755 public/
chmod 644 public/index.php
chmod -R 775 storage/ bootstrap/cache/
```

### 4. Очистите кэш:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## ✅ Проверка:

Откройте в браузере:
- https://im-edok.ru/test.php - должен показать информацию о PHP
- https://im-edok.ru/ - должен показать сайт

## 📞 Если ничего не помогает:

Напишите в поддержку хостинга:

```
Здравствуйте!

Прошу изменить DocumentRoot для домена im-edok.ru на:
/home/g/gamechann2/im-edok_ru/public

И включить модули Apache:
- mod_rewrite
- mod_deflate
- mod_headers

Спасибо!
```
