# PROJECT_RULES.md

# Demo ERP — правила архитектуры, разработки, проверки и уведомлений

Проект: **Demo ERP**  
Назначение: мини ERP-система для демонстрации части функционала клиенту.  
Цель: быстро собрать стабильный, визуально аккуратный и понятный демо-контур без тяжёлых фреймворков и без архитектурного усложнения.

---

## 1. Текущий контур проекта

### Локальная папка

```text
C:\demoERP
```

### GitHub

```text
https://github.com/caymanlogistic-crypto/demo-erp
```

### Сервер

```text
/home/s/spugovxsim/public_html/demoERP
```

### Публичная ссылка

```text
https://mg-log.ru/demoERP/public/
```

### Основная ветка

```text
main
```

### Автодеплой

Автодеплой настроен через GitHub Actions.

После каждого `git push` в ветку `main` проект автоматически выкладывается на сервер в:

```text
/home/s/spugovxsim/public_html/demoERP/
```

---

## 2. Технологический стек

Использовать:

```text
PHP >=8.4 <8.6
Composer
PSR-4 autoload
vlucas/phpdotenv
Vanilla PHP
Vanilla CSS
Vanilla JavaScript
HTML
```

Не использовать:

```text
Laravel
Symfony
Yii
React
Vue
Angular
SPA-подход
Tailwind build pipeline
Webpack/Vite
ORM
тяжёлые admin templates
сложные frontend-сборщики
```

Главный принцип:

```text
Предсказуемая простота важнее архитектурной красоты.
```

---

## 3. Текущая структура проекта

```text
demoERP/
├── app/
│   ├── Core/
│   ├── Modules/
│   │   ├── Auth/
│   │   └── Home/
│   └── Views/
├── bootstrap/
├── config/
├── public/
│   └── index.php
├── routes/
├── storage/
│   └── logs/
├── .github/
│   └── workflows/
│       └── deploy.yml
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
└── PROJECT_RULES.md
```

---

## 4. Главные архитектурные правила

### 4.1. Entry point

Единственная публичная точка входа:

```text
public/index.php
```

Нельзя создавать отдельные публичные PHP-файлы вразнобой.

Правильно:

```text
public/index.php
```

Неправильно:

```text
public/clients.php
public/orders.php
public/test.php
```

---

### 4.2. Public directory

В `public/` разрешено хранить только:

```text
index.php
assets/css/
assets/js/
assets/img/
```

Вся бизнес-логика должна быть вне `public/`.

---

### 4.3. App directory

В `app/` хранится основная логика проекта:

```text
app/Core        базовые классы ядра
app/Modules     функциональные модули
app/Views       шаблоны
```

---

### 4.4. Modules

Каждый функциональный блок оформлять как модуль:

```text
app/Modules/Clients
app/Modules/Orders
app/Modules/Flights
app/Modules/Documents
```

Пример структуры модуля:

```text
app/Modules/Clients/
├── Controllers/
├── Repositories/
├── Services/
└── Validators/
```

Не смешивать весь код в одном файле.

---

### 4.5. Views

Шаблоны хранить в:

```text
app/Views/
```

Рекомендуемая структура:

```text
app/Views/layouts/main.php
app/Views/home/index.php
app/Views/clients/index.php
app/Views/clients/create.php
app/Views/clients/edit.php
```

В шаблонах нельзя размещать тяжёлую бизнес-логику.

Разрешено:

```text
вывод переменных
простые условия
простые циклы
подключение компонентов
```

Запрещено:

```text
SQL-запросы
обработка POST
сложные вычисления
создание бизнес-сущностей
```

---

## 5. Composer и автозагрузка

PSR-4 namespace проекта:

```json
{
  "App\\": "app/"
}
```

После добавления новых классов выполнять:

```powershell
composer dump-autoload
```

Проверять:

```powershell
composer validate
```

---

## 6. ENV-настройки

Файл `.env` не коммитить.

В Git хранится только:

```text
.env.example
```

Текущие базовые параметры:

```env
APP_NAME="Demo ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=demo_erp
DB_USER=root
DB_PASS=
```

Все доступы к базе, ключи и секреты хранить только в `.env` или GitHub Secrets.

---

## 7. Git-правила

Рабочая ветка:

```text
main
```

Перед изменениями:

```powershell
git status
```

После изменений:

```powershell
git add .
git commit -m "type(scope): short description"
git push
```

Формат сообщений:

```text
feat(clients): add client list screen
fix(auth): handle invalid login
ui(layout): polish sidebar and dashboard
ci(deploy): update deployment workflow
docs(project): add architecture rules
```

Не коммитить:

```text
.env
vendor/
storage/logs/*.log
временные файлы
архивы
дампы БД
личные ключи
```

---

## 8. Автодеплой

GitHub Actions файл:

```text
.github/workflows/deploy.yml
```

Secrets в GitHub:

```text
SSH_HOST
SSH_USER
SSH_PRIVATE_KEY
DEPLOY_PATH
```

Текущие значения:

```text
SSH_HOST = 77.222.40.251
SSH_USER = spugovxsim
DEPLOY_PATH = /home/s/spugovxsim/public_html/demoERP/
```

Приватный ключ хранится только в GitHub Secret:

```text
SSH_PRIVATE_KEY
```

После каждого `git push` нужно проверить GitHub Actions.

Если workflow красный — не продолжать разработку, пока причина не исправлена.

---

## 9. Runtime-first правило

Код считается готовым только после runtime-проверки.

Недостаточно:

```text
просто написать код
просто сделать php -l
просто закоммитить
```

Обязательно:

```text
1. Проверить синтаксис PHP.
2. Проверить локальный запуск.
3. Проверить страницу в браузере.
4. Запушить.
5. Проверить GitHub Actions.
6. Проверить страницу на сервере.
7. Отправить уведомление в MAX.
```

---

## 10. Минимальный набор проверок

### 10.1. Проверка Composer

```powershell
composer validate
composer dump-autoload
```

### 10.2. Проверка PHP

Для каждого изменённого PHP-файла:

```powershell
php -l path\to\file.php
```

Минимум всегда:

```powershell
php -l public\index.php
```

### 10.3. Локальный запуск

```powershell
php -S localhost:8000 -t public
```

Открыть:

```text
http://localhost:8000
```

### 10.4. Проверка после деплоя

Открыть:

```text
https://mg-log.ru/demoERP/public/
```

Страница не должна давать:

```text
500 Internal Server Error
404 Not Found
blank page
PHP Fatal error
```

---

## 11. Серверные проверки

На сервере PHP 8.4:

```bash
/usr/bin/php8.4 -v
```

Проверка файла:

```bash
cd /home/s/spugovxsim/public_html/demoERP
/usr/bin/php8.4 -l public/index.php
```

Проверка структуры:

```bash
ls -la /home/s/spugovxsim/public_html/demoERP
ls -la /home/s/spugovxsim/public_html/demoERP/public
```

---

## 12. Логи

Папка логов:

```text
storage/logs/
```

Логи не коммитить.

Если появляется ошибка 500, сначала смотреть:

```bash
tail -50 /home/s/spugovxsim/public_html/demoERP/storage/logs/app.log
```

Если файла ещё нет — проверить системные ошибки PHP/hosting panel.

---

## 13. UI-правила

Цель интерфейса:

```text
мини ERP должна выглядеть как рабочая B2B-система, а не как учебный шаблон.
```

Стиль:

```text
desktop-first
плотная операционная сетка
понятная навигация
аккуратные таблицы
строгие формы
умеренно современный внешний вид
```

Не делать:

```text
игрушечный SaaS
лендинг вместо ERP
огромные отступы
мобильный-first интерфейс
яркий случайный дизайн
admin template из интернета
```

Разрешено:

```text
vanilla CSS
CSS variables
простые компоненты
таблицы
карточки
статусы
панели фильтров
компактные формы
```

---

## 14. Базовые UI-компоненты

В системе должны постепенно появиться единые компоненты:

```text
sidebar
topbar
page header
buttons
forms
inputs
selects
tables
status badges
cards
alerts
empty states
pagination
modal/confirm
```

Не создавать каждый экран своим стилем.

Все визуальные решения выносить в:

```text
public/assets/css/app.css
```

JS — в:

```text
public/assets/js/app.js
```

---

## 15. Безопасность

Обязательно:

```text
экранировать HTML-вывод
не выводить сырые пользовательские данные
не хранить секреты в Git
не коммитить приватные ключи
не показывать stack trace на production
```

Для вывода HTML использовать:

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

Позже можно добавить общий helper:

```php
e($value)
```

---

## 16. Работа с базой данных

Пока БД может быть не подключена.

Когда будет добавлена БД:

```text
использовать PDO
не использовать ORM
использовать prepared statements
не собирать SQL через конкатенацию пользовательского ввода
```

Правильно:

```php
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id');
$stmt->execute(['id' => $id]);
```

Неправильно:

```php
$pdo->query("SELECT * FROM clients WHERE id = " . $_GET['id']);
```

---

## 17. Приоритет разработки

Сначала делаем демонстрационный контур:

```text
1. Главный экран
2. Клиенты / контрагенты
3. Заявки
4. Рейсы
5. Документы
6. Авторизация
7. Простая роль администратора
```

Не начинать с чрезмерной архитектуры.

Сначала:

```text
видимый рабочий функционал
понятная навигация
стабильный runtime
```

Потом:

```text
расширение модулей
валидация
права
сложные связи
отчёты
```

---

## 18. Запрещённые действия

Кодеру запрещено:

```text
ломать автодеплой
менять DEPLOY_PATH без согласования
коммитить .env
коммитить vendor/
добавлять Laravel/Symfony
добавлять frontend-сборщик
создавать хаотичные PHP-файлы в public/
удалять рабочий public/index.php без замены
оставлять проект в состоянии 500
пушить без локальной проверки
считать работу завершённой без MAX-уведомления
```

---

## 19. Обязательный порядок работы кодера

Перед началом:

```powershell
cd C:\demoERP
git status
git pull
```

После изменений:

```powershell
composer validate
composer dump-autoload
php -l public\index.php
php -S localhost:8000 -t public
```

Проверить в браузере:

```text
http://localhost:8000
```

Затем:

```powershell
git status
git add .
git commit -m "type(scope): description"
git push
```

После push:

```text
1. Проверить GitHub Actions.
2. Убедиться, что workflow зелёный.
3. Открыть production-ссылку.
4. Убедиться, что страница работает.
5. Отправить MAX-уведомление.
```

Production-ссылка:

```text
https://mg-log.ru/demoERP/public/
```

---

## 20. Правило завершения задачи

Задача считается завершённой только если:

```text
код написан
локально работает
php -l проходит
composer validate проходит
изменения закоммичены
изменения запушены
GitHub Actions зелёный
production-ссылка открывается
нет 500 ошибки
отправлено MAX-уведомление
```

Если что-то сломалось — не скрывать. Сразу сообщить:

```text
что изменено
что проверено
где ошибка
какой лог/вывод команды
что нужно исправить
```

---

## 21. Рекомендация по coding agent

Для задач по этому проекту использовать:

```text
deepseek-v4-pro
```

Контекст:

```text
128k минимум
256k–512k для задач с несколькими модулями
1M только для большого рефакторинга или полного анализа проекта
```

Для быстрых мелких правок можно использовать:

```text
deepseek-v4-flash
```

Но для архитектуры, автодеплоя, модулей и runtime-стабильности предпочтительно:

```text
deepseek-v4-pro
```

---

## 22. Обязательные уведомления в MAX

В проект `demoERP` **не добавлять локальный `max_notify.php`**.

Для уведомлений использовать уже существующий рабочий внешний MAX-уведомитель:

```text
http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php
```

Кодер обязан отправлять уведомление в MAX в двух случаях:

```text
1. Работа завершена.
2. Требуется участие Владимира.
```

---

### 22.1. Когда отправлять уведомление о завершении

Перед отправкой уведомления кодер обязан выполнить:

```text
1. Проверить код локально.
2. Выполнить composer validate.
3. Выполнить composer dump-autoload.
4. Выполнить php -l для изменённых PHP-файлов.
5. Проверить локальный запуск.
6. Сделать commit.
7. Сделать push.
8. Проверить GitHub Actions.
9. Проверить production-ссылку.
10. Только после этого отправить MAX-уведомление.
```

Production-ссылка:

```text
https://mg-log.ru/demoERP/public/
```

---

### 22.2. PowerShell-вызов уведомления о завершении

Пример:

```powershell
$Message = [uri]::EscapeDataString("Demo ERP: задача завершена. Проверки пройдены, push выполнен, GitHub Actions зелёный, production открывается: https://mg-log.ru/demoERP/public/")
Invoke-WebRequest "http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php?message=$Message"
```

---

### 22.3. PowerShell-вызов, если нужно участие владельца

Если задача заблокирована или требуется решение владельца, кодер обязан сразу отправить уведомление:

```powershell
$Message = [uri]::EscapeDataString("Demo ERP: требуется участие Владимира. Причина: нужно подтвердить UI / добавить secret / дать доступ / проверить production.")
Invoke-WebRequest "http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php?message=$Message"
```

---

### 22.4. Linux/server curl-вариант

На сервере можно использовать:

```bash
curl "http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php?message=Demo%20ERP%3A%20task%20finished"
```

Для русских сообщений предпочтительно использовать PowerShell-вариант с `[uri]::EscapeDataString(...)`, чтобы не сломать кодировку.

---

### 22.5. Формат сообщения о завершении

Сообщение должно содержать:

```text
Проект: Demo ERP
Задача:
Что сделано:
Какие файлы изменены:
Проверки:
Commit:
GitHub Actions:
Production URL:
Что нужно проверить владельцу:
```

Пример полного сообщения:

```text
Проект: Demo ERP
Задача: добавить главный экран мини ERP
Что сделано: обновлён public/index.php, добавлен базовый layout и CSS
Файлы: public/index.php, app/Views/layouts/main.php, public/assets/css/app.css
Проверки: composer validate OK, php -l OK, локально OK, GitHub Actions зелёный
Commit: abc1234
Production URL: https://mg-log.ru/demoERP/public/
Что проверить владельцу: внешний вид главного экрана
```

---

### 22.6. Запрещено

Нельзя считать работу завершённой без MAX-уведомления.

Нельзя отправлять сообщение `готово`, если:

```text
не выполнен push
не проверен GitHub Actions
production не открывается
есть PHP Fatal error
есть 500 Internal Server Error
```

Нельзя добавлять в `demoERP` локальный `support/max_notify.php`, если отдельно не согласовано. Используется внешний рабочий URL.

---

## 23. Главный принцип проекта

```text
Сначала стабильный работающий demo ERP.
Потом расширение.
Не ломать рабочее ради красоты.
Не усложнять без необходимости.
Каждое изменение проверять в runtime.
Финальный шаг любой задачи — MAX-уведомление владельцу.
```
