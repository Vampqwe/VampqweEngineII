# VampqweEngineII

Легкий MVC-каркас на PHP 8.2+ с Composer, PHP-DI, FastRoute, Twig и PDO/MySQL.

## Запуск

Требуются PHP 8.2+, Composer и расширения `pdo` и `pdo_mysql`.

```sh
composer install
cp config.env.example config.env
composer test
php -S 127.0.0.1:8000 -t public
```

Откройте `http://127.0.0.1:8000`. Настройки приложения и MySQL задаются в `config.env`; файл с локальными значениями не добавляется в git.

## Структура

- `public/` — единственная публичная директория и front controller.
- `src/Controller/` — MVC-контроллеры.
- `src/Http/` — маршрутизация и обработка HTTP-запросов.
- `src/Http/MiddlewareStack.php` — расширяемая цепочка HTTP middleware.
- `src/View/` и `templates/` — рендеринг Twig-шаблонов.
- `src/Database/` — создание PDO-подключения к MySQL.
- `config/routes.php` — декларативная таблица маршрутов.
- `bootstrap/app.php` — конфигурация, сессия и DI-контейнер.

Добавляйте маршруты в `config/routes.php`, контроллеры в `src/Controller/`, а зависимости объявляйте в контейнере в `bootstrap/app.php`. Контроллеры получают зависимости через конструктор.

Для запросов внедряйте `Vampqwe\Engine\Database\Database` в repository-классы. Методы `fetchAll`, `fetchOne` и `execute` принимают параметры отдельно от SQL; не вставляйте пользовательский ввод в SQL-строку. `transaction()` поддерживает вложенные операции через savepoints.

## Безопасность

Twig экранирует HTML автоматически; PDO использует подготовленные запросы и отключенные эмулируемые prepare. HTTP-ответы получают CSP и базовые защитные заголовки. Сессии используют HttpOnly, SameSite=Lax и строгий режим. CSRF middleware проверяет все запросы, изменяющие состояние. Добавляйте токен в Twig-формы через `<input type="hidden" name="_token" value="{{ csrf_token() }}">`; API-клиенты могут передавать `X-CSRF-TOKEN`. При HTTPS установите `SESSION_SECURE_COOKIE=true`. В production задайте `APP_ENV=production` и `APP_DEBUG=false`; не публикуйте `config.env` и не отключайте проверку TLS.

Это стартовый каркас, а не гарантия защиты от всех уязвимостей. Подключите аутентификацию и авторизацию по требованиям конкретного проекта; валидируйте входные данные и проверяйте права доступа на сервере.