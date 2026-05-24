# PROJECT_RULES.md

# Demo ERP — правила архитектуры, разработки, проверки и уведомлений

Проект: **Demo ERP**  
Назначение: мини ERP-система для демонстрации части функционала клиенту.  
Цель: быстро собрать стабильный, понятный и расширяемый демо-контур без тяжёлых фреймворков и без архитектурного усложнения.

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

### Ветка

```text
main
```

### Сервер

```text
/home/s/spugovxsim/public_html/demoERP
```

### Production URL

```text
https://mg-log.ru/demoERP/public/
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
PDO
MySQL
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
│   │   ├── Feo/
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
├── PROJECT_RULES.md
└── README.md
```

Фактическая структура может отличаться по мере развития проекта, но архитектурный принцип сохраняется:

```text
public/ — только публичная точка входа и assets
app/ — весь PHP-код приложения
storage/ — логи и runtime-файлы
```

---

## 4. Главные архитектурные правила

### 4.1. Единственная публичная точка входа

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
public/index22.php
public/feo.php
public/ajax.php
```

### 4.2. Public directory

В `public/` разрешено хранить только:

```text
index.php
assets/css/
assets/js/
assets/img/
```

Вся бизнес-логика должна быть вне `public/`.

### 4.3. App directory

В `app/` хранится основная логика проекта:

```text
app/Core        базовые классы ядра
app/Modules     функциональные модули
app/Views       шаблоны
```

### 4.4. Modules

Каждый функциональный блок оформлять как модуль:

```text
app/Modules/Clients
app/Modules/Orders
app/Modules/Flights
app/Modules/Documents
app/Modules/Feo
```

Пример структуры модуля:

```text
app/Modules/Feo/
├── Controllers/
├── Repositories/
├── Services/
├── Support/
└── Validators/
```

Не смешивать весь код в одном файле.

### 4.5. Views

Шаблоны хранить в:

```text
app/Views/
```

Рекомендуемая структура:

```text
app/Views/layouts/main.php
app/Views/home/index.php
app/Views/feo/index.php
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

Все доступы к базе, ключи и секреты хранить только в `.env`, серверном окружении или GitHub Secrets.

---

## 7. Git-правила

Рабочая ветка:

```text
main
```

Перед изменениями:

```powershell
git status
git pull
```

После изменений:

```powershell
git add .
git commit -m "type(scope): short description"
git push
```

Формат сообщений:

```text
feat(feo): add read-only requests listing
fix(auth): handle invalid login
ui(layout): polish sidebar and dashboard
ci(deploy): update deployment workflow
docs(project): update coder workflow rules
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
секретные токены
MAX token
MAX notify key
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
2. Проверить Composer/autoload.
3. Проверить локальный запуск.
4. Проверить страницу в браузере.
5. Запушить.
6. Проверить GitHub Actions.
7. Проверить страницу на production.
8. Отправить MAX-уведомление.
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

Проверить в браузере:

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

Но при переносе старого функционала действует отдельное правило:

```text
сначала переносится голый функционал без дизайна;
дизайн натягивается отдельным этапом после проверки логики.
```

Стиль будущего интерфейса:

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

Использовать:

```text
PDO
prepared statements
.env для доступов
```

Не использовать:

```text
ORM
SQL через конкатенацию пользовательского ввода
реальные DB-доступы в Git
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

## 17. Важно: локально БД не настроена

На локальной машине БД по умолчанию не настроена.

Поэтому локальная проверка не должна требовать реального подключения к MySQL.

Код должен быть написан так, чтобы при отсутствии локальной БД:

```text
не было PHP Fatal error
не было белого экрана
не было 500 из-за отсутствия подключения
главная страница продолжала работать
модульная страница открывалась
пользователь видел понятное сообщение
```

Пример понятного сообщения:

```text
База данных не подключена. Проверьте параметры .env.
```

или:

```text
Функционал требует подключения к БД.
```

Локально обязательно проверить:

```text
composer validate
composer dump-autoload
php -l для всех изменённых PHP-файлов
php -S localhost:8000 -t public
http://localhost:8000/
http://localhost:8000/?module=feo
```

Реальную проверку выборки из БД выполнять после push на production/server.

---

## 18. Правила переноса функционала из старой ERP

Старые файлы могут лежать локально, например:

```text
C:\ТЕСТ УДАЛИТЬ
```

Главное правило:

```text
НЕ копировать старый файл целиком в public/.
```

Правильный процесс:

```text
старый ERP-файл
        ↓
анализ логики
        ↓
выделение нужного функционала
        ↓
адаптация под app/Modules/...
        ↓
подключение через public/index.php / routing
        ↓
runtime-проверка
        ↓
commit + push + deploy
```

Сначала переносится голый функционал.

Не переносить:

```text
старый CSS
старую тему
старые украшательства
старую навигацию
случайный JS
старые файлы как есть
```

---

## 19. Текущая задача переноса ФЭО

Функционал переносится из старого файла:

```text
C:\ТЕСТ УДАЛИТЬ\index22.php
```

Также для анализа может использоваться:

```text
C:\ТЕСТ УДАЛИТЬ\config.php
```

`config.php` использовать только для понимания старого подключения.  
Секреты из `config.php` не переносить в Git.

### 19.1. Что НЕ переносить из index22.php

Категорически не переносить:

```text
создание таблицы feo, если её нет
загрузку Excel .xlsx
разбор Excel через PhpSpreadsheet
сопоставление колонок Excel с полями БД
анализ новых и существующих заявок для импорта
импорт только новых заявок
импорт с перезаписью существующих
редактирование строки
удаление строки
подтягивание стоимости из include/price.php
старый CSS
старую визуальную тему
старые украшательства
старую навигацию
копирование index22.php целиком в public/
установку phpoffice/phpspreadsheet
```

Excel-импорт сейчас не переносится, поэтому PhpSpreadsheet не нужен.

### 19.2. Что переносить

Перенести read-only функционал и поведение страницы из `index22.php`:

```text
чтение данных из существующей таблицы feo
вывод списка заявок
реальные заголовки колонок
реальный порядок колонок
реальные поля таблицы
HTTP/AJAX-выдача данных для списка
фильтр по номерам заявок
поиск без обязательного нажатия кнопки, если он был в старом файле
фильтр “доступные”
фильтр “маршруты”
фильтр “рейсы”
использование status_blocks и flights только для отображения/фильтрации
вывод ошибок
сообщения при пустом результате
минимальная HTML-страница без дизайна
```

Старый `index22.php` использовать как источник алгоритма:

```text
какие поля есть в feo
какие заголовки выводились
какой порядок колонок
как выбираются заявки
как работает фильтр по номерам
как работает автопоиск
как определяется “доступные”
как определяется “маршруты”
как определяется “рейсы”
как связаны feo / status_blocks / flights
```

Кодеру запрещено выдумывать названия колонок, порядок колонок и бизнес-логику.

---

## 20. URL для модуля ФЭО

Минимальный URL:

```text
https://mg-log.ru/demoERP/public/?module=feo
```

Допустимые варианты фильтров:

```text
https://mg-log.ru/demoERP/public/?module=feo&filter=available
https://mg-log.ru/demoERP/public/?module=feo&filter=routes
https://mg-log.ru/demoERP/public/?module=feo&filter=flights
https://mg-log.ru/demoERP/public/?module=feo&numbers=123,456,789
```

Если используется JSON action:

```text
https://mg-log.ru/demoERP/public/?module=feo&action=list
https://mg-log.ru/demoERP/public/?module=feo&action=list&filter=available
https://mg-log.ru/demoERP/public/?module=feo&action=list&filter=routes
https://mg-log.ru/demoERP/public/?module=feo&action=list&filter=flights
```

---

## 21. Проверки после push на сервере

После push обязательно:

```text
1. Открыть GitHub Actions.
2. Дождаться зелёного workflow.
3. Проверить production.
```

Production URL:

```text
https://mg-log.ru/demoERP/public/
https://mg-log.ru/demoERP/public/?module=feo
https://mg-log.ru/demoERP/public/?module=feo&filter=available
https://mg-log.ru/demoERP/public/?module=feo&filter=routes
https://mg-log.ru/demoERP/public/?module=feo&filter=flights
```

Если production показывает ошибку подключения к БД, отсутствия таблицы или отсутствия поля:

```text
зафиксировать точный текст ошибки
не считать задачу завершённой
отправить MAX-уведомление “требуется участие Владимира”
```

Если GitHub Actions красный — задача не завершена.

---

## 22. Обязательные MAX-уведомления

MAX-уведомления обязательны.

Кодер обязан отправлять уведомление в двух случаях:

```text
1. Работа завершена.
2. Требуется участие Владимира.
```

### 22.1. Важное уточнение по файлам MAX

В проект `demoERP` не добавлять локальный `max_notify.php`.

Файл:

```text
http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php
```

напрямую не вызывать.

Это библиотека функций, а не публичный endpoint.

Правильный endpoint для отправки уведомлений:

```text
http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php
```

Параметры endpoint:

```text
key
text
format
```

Формат:

```text
format=markdown
```

Ключ уведомления не коммитить в Git.

В промтах и документации использовать placeholder:

```text
<MAX_NOTIFY_KEY>
```

Если владелец проекта временно дал тестовый ключ, его можно использовать только для ручного вызова, но нельзя добавлять в репозиторий.

### 22.2. PowerShell-вызов MAX

Команда 1:

```powershell
$Text = [uri]::EscapeDataString("Demo ERP: задача завершена. Push выполнен, GitHub Actions зелёный, production проверен.")
```

Команда 2:

```powershell
Invoke-WebRequest "http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php?key=<MAX_NOTIFY_KEY>&text=$Text&format=markdown" -UseBasicParsing
```

Команды выполнять отдельно, не одной длинной строкой.

### 22.3. Bash-вызов MAX

Команда 1:

```bash
TEXT=$(python3 -c 'import urllib.parse; print(urllib.parse.quote("Demo ERP: задача завершена. Push выполнен, GitHub Actions зелёный, production проверен."))')
```

Команда 2:

```bash
curl "http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php?key=<MAX_NOTIFY_KEY>&text=$TEXT&format=markdown"
```

### 22.4. Уведомление о завершении работы

Перед отправкой уведомления “готово” кодер обязан выполнить:

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

Пример текста:

```text
Demo ERP: задача завершена.
Проверки пройдены.
Push выполнен.
GitHub Actions зелёный.
Production проверен: https://mg-log.ru/demoERP/public/
```

### 22.5. Уведомление, если нужно участие Владимира

Если задача заблокирована или требуется решение владельца, кодер обязан сразу отправить уведомление.

Пример текста:

```text
Demo ERP: требуется участие Владимира.
Причина: на production отсутствуют параметры .env / не хватает таблицы / не хватает поля / нужно подтвердить UI / нужен доступ.
```

### 22.6. Запрещено

Нельзя считать работу завершённой без MAX-уведомления.

Нельзя отправлять “готово”, если:

```text
не выполнен push
не проверен GitHub Actions
GitHub Actions красный
production не открывается
есть PHP Fatal error
есть 500 Internal Server Error
есть белый экран
реальная проверка с БД не выполнена и требуется вмешательство владельца
```

---

## 23. Правило завершения задачи

Задача считается завершённой только если:

```text
код написан
локально работает
php -l проходит
composer validate проходит
composer dump-autoload проходит
изменения закоммичены
изменения запушены
GitHub Actions зелёный
production-ссылка открывается
нет 500 ошибки
нет PHP Fatal error
MAX-уведомление отправлено
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

## 24. Обязательный порядок работы кодера

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

## 25. Запрещённые действия

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
пушить без production-проверки
пушить секреты, ключи, токены
дёргать Support/max_notify.php как endpoint
```

---

## 26. Правило HTTP-проверок в Windows PowerShell

В Windows PowerShell запрещено использовать Linux-синтаксис `curl`, потому что `curl` в PowerShell часто является alias для `Invoke-WebRequest`.

Запрещено использовать:

```powershell
curl -s ...
curl -L ...
curl -w ...
curl -I ...
```

Такие команды могут зависнуть, перейти в интерактивный режим и начать спрашивать параметр `Uri`, из-за чего Cline/кодер останавливается и ждёт участия владельца.

Для HTTP-проверок в Windows PowerShell разрешено использовать только `curl.exe` или `Invoke-WebRequest`.

Правильно через `curl.exe`:

```powershell
curl.exe -s -k -L -w "%{http_code}" "https://mg-log.ru/demoERP/public/" -o NUL
```

Правильно через `Invoke-WebRequest`:

```powershell
$response = Invoke-WebRequest "https://mg-log.ru/demoERP/public/" -UseBasicParsing
$response.StatusCode
```

Для проверки локального сервера:

```powershell
curl.exe -s -k -L -w "%{http_code}" "http://localhost:8000/" -o NUL
```

или:

```powershell
$response = Invoke-WebRequest "http://localhost:8000/" -UseBasicParsing
$response.StatusCode
```

Все команды проверки должны быть неинтерактивными. Команда не должна ждать ручного ввода пользователя.

---

## 27. Правило MAX-уведомлений: только notify_max.php

Для отправки MAX-уведомлений запрещено вызывать:

```text
http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php
```

Этот файл является PHP-библиотекой функций, а не публичным endpoint.

Правильный endpoint:

```text
http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php
```

Обязательные параметры:

```text
key
text
format=markdown
```

Правильный PowerShell-вызов:

```powershell
$Text = [uri]::EscapeDataString("Demo ERP: задача завершена. Push выполнен, GitHub Actions зелёный, production проверен.")
```

```powershell
Invoke-WebRequest "http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php?key=<MAX_NOTIFY_KEY>&text=$Text&format=markdown" -UseBasicParsing
```

Команды выполнять отдельно.

Запрещено использовать `Support/max_notify.php` как URL даже если он возвращает `200 OK`. `200 OK` от этого файла не означает, что сообщение отправлено.

---

## 28. Рекомендация по coding agent

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

Но для архитектуры, автодеплоя, модулей, миграции старой ERP и runtime-стабильности предпочтительно:

```text
deepseek-v4-pro
```

---

## 29. Главный принцип проекта

```text
Сначала стабильный работающий demo ERP.
Потом расширение.
Не ломать рабочее ради красоты.
Не усложнять без необходимости.
Каждое изменение проверять в runtime.
Каждое завершение подтверждать MAX-уведомлением.
```
