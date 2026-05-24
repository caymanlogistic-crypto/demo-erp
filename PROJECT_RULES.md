# PROJECT_RULES.md

# Demo ERP — базовые правила архитектуры, разработки, проверок, команд и уведомлений

Проект: **Demo ERP**  
Назначение: мини ERP-система для демонстрации части функционала клиенту.  
Цель: быстро собрать стабильный, понятный и расширяемый демо-контур без тяжёлых фреймворков и без архитектурного усложнения.

Главный принцип:

```text
Сначала стабильный рабочий функционал.
Потом дизайн.
Каждое изменение проверяется в runtime.
```

---

## 0. Обязательное правило для кодера перед каждой задачей

Перед началом любой новой задачи кодер обязан заново открыть и полностью перечитать:

```text
C:\demoERP\PROJECT_RULES.md
```

Работать строго по этому файлу.

Если текущий промт конфликтует с `PROJECT_RULES.md`, кодер обязан:

```text
1. остановиться;
2. явно написать, в чём конфликт;
3. не выполнять спорное действие без уточнения владельца.
```

Особенно обязательно соблюдать:

```text
runtime-first проверки
правила Windows PowerShell
запрет Linux-команд в PowerShell
запрет curl без .exe
запрет Support/max_notify.php как endpoint
правильный MAX endpoint notify_max.php
запрет коммита .env, vendor, токенов, ключей и секретов
обязательный commit + push + GitHub Actions + production check + MAX notification
```

---

## 1. Контур проекта

### Локальная папка проекта

```text
C:\demoERP
```

### GitHub

```text
https://github.com/caymanlogistic-crypto/demo-erp
```

### Рабочая ветка

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

Главный технический принцип:

```text
Предсказуемая простота важнее архитектурной красоты.
```

---

## 3. Базовая структура проекта

```text
demoERP/
├── app/
│   ├── Core/
│   ├── Modules/
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
public/import.php
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
app/Modules/<ModuleName>
```

Пример структуры модуля:

```text
app/Modules/<ModuleName>/
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
app/Views/<module>/index.php
app/Views/<module>/_table.php
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

## 6. ENV-настройки и секреты

Файл `.env` не коммитить.

В Git хранится только:

```text
.env.example
```

Базовые параметры:

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

Все доступы к базе, ключи и секреты хранить только в:

```text
.env
серверном окружении
GitHub Secrets
```

Запрещено коммитить:

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
DB-пароли
API-ключи
```

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
feat(module): add feature
fix(module): fix behavior
docs(project): update coder workflow rules
ci(deploy): update deployment workflow
```

Запрещено без отдельного разрешения владельца:

```powershell
git reset --hard
git clean -fd
git checkout -- .
git restore .
git revert ...
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
4. Проверить страницу в браузере или HTTP-проверкой.
5. Сделать commit.
6. Сделать push.
7. Проверить GitHub Actions.
8. Проверить страницу на production.
9. Отправить MAX-уведомление или зафиксировать ошибку MAX в отчёте.
```

---

## 10. Минимальный набор проверок

### 10.1. Composer

```powershell
composer validate
composer dump-autoload
```

### 10.2. PHP syntax

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

Проверить:

```text
http://localhost:8000
```

Если задача добавляет модуль, проверить URL модуля, указанный в промте.

### 10.4. Production check

Проверить:

```text
https://mg-log.ru/demoERP/public/
```

Если задача добавляет модуль, проверить production URL модуля, указанный в промте.

Страница не должна давать:

```text
500 Internal Server Error
404 Not Found
blank page
PHP Fatal error
```

---

## 11. Локально БД может быть не настроена

На локальной машине БД по умолчанию может быть не настроена.

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

Реальную проверку выборки из БД выполнять после push на production/server.

---

## 12. Работа с базой данных

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

## 13. Логи

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

## 14. Правила переноса функционала из старой ERP

Этот раздел описывает общий подход. Конкретная текущая задача, конкретные файлы и конкретные колонки должны быть указаны в отдельном промте, а не в `PROJECT_RULES.md`.

Главное правило:

```text
НЕ копировать старый файл целиком в public/.
```

Правильный процесс:

```text
старый ERP-файл
        ↓
анализ фактической логики
        ↓
выделение функционала
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

Не переносить как отдельную тему:

```text
старый CSS
старые украшательства
старую навигацию
визуальную тему
```

Но функциональное поведение экрана переносить полностью, если это указано в промте задачи.

Запрещено:

```text
придумывать новые колонки
переименовывать колонки “для красоты”
менять порядок колонок без задания
делать “по смыслу”, если есть исходный файл
заменять старый UX упрощённой таблицей без разрешения
```

Если чего-то нельзя перенести без дополнительного файла/таблицы — написать это в отчёте.

---

## 15. UI-правила

Цель интерфейса:

```text
мини ERP должна выглядеть как рабочая B2B-система, а не как учебный шаблон.
```

Но при переносе старого функционала действует отдельное правило:

```text
сначала переносится голый функционал без отдельного дизайн-этапа;
дизайн натягивается отдельным этапом после проверки логики.
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

## 16. Правила команд для Windows PowerShell

Кодер по умолчанию работает в **Windows PowerShell**.

Запрещено использовать Linux-команды в PowerShell.

### 16.1. Переход по папкам

Разрешено:

```powershell
cd C:\demoERP
cd "C:\ТЕСТ УДАЛИТЬ"
cd C:\fregat\map
Get-Location
pwd
```

Команды выполнять отдельно. Не склеивать через `&&`.

Запрещено:

```powershell
cd "C:\demoERP" && git status
```

### 16.2. Git

Разрешено:

```powershell
git status
git log --oneline -5
git diff --stat
git diff -- path\to\file.php
git add path\to\file.php
git add .
git commit -m "message"
git push
git pull
```

### 16.3. Composer

Разрешено:

```powershell
composer validate
composer dump-autoload
composer install
```

Запрещено без отдельного разрешения:

```powershell
composer require ...
composer update
composer remove ...
```

### 16.4. PHP

Разрешено:

```powershell
php -v
php -l public\index.php
php -l path\to\changed-file.php
php -S localhost:8000 -t public
```

Если запускается `php -S`, после проверки сервер нужно остановить через `Ctrl+C` или использовать отдельный терминал.

### 16.5. Поиск по файлам

Запрещено в PowerShell:

```text
grep
head
tail
sed
awk
cat с Linux-синтаксисом
find с Linux-синтаксисом
```

Использовать `Select-String`:

```powershell
Select-String -Path ".\file.php" -Pattern "search_text"
```

```powershell
Select-String -Path ".\file.php" -Pattern "SELECT","JOIN" | Select-Object -First 100
```

Посмотреть начало файла:

```powershell
Get-Content ".\file.php" | Select-Object -First 120
```

Посмотреть часть файла:

```powershell
Get-Content ".\file.php" | Select-Object -Skip 300 -First 120
```

Найти PHP-файлы:

```powershell
Get-ChildItem -Recurse -File -Filter "*.php"
```

### 16.6. HTTP-проверки в PowerShell

В Windows PowerShell запрещено использовать `curl` без `.exe`.

Запрещено:

```powershell
curl -s ...
curl -L ...
curl -w ...
curl -I ...
curl "url"
```

Причина: в PowerShell `curl` часто является alias для `Invoke-WebRequest`, из-за этого команды зависают и спрашивают `Uri`.

Разрешено только `curl.exe`:

```powershell
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/"
```

```powershell
curl.exe -s -k -L -w "%{http_code}" "http://localhost:8000/" -o NUL
```

Или `Invoke-WebRequest`:

```powershell
$response = Invoke-WebRequest "http://localhost:8000/" -UseBasicParsing
$response.StatusCode
```

Не использовать `-SkipCertificateCheck`, потому что в текущей версии PowerShell этот параметр может отсутствовать.

Для HTTPS production использовать:

```powershell
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/"
```

---

## 17. Запрещённые команды в Windows PowerShell

Не использовать:

```text
head -100
tail -50
grep -R
sed -n
awk
curl -s
curl -L
curl -w
curl -I
rm -rf
chmod
chown
sudo
TEXT=$(...)
команда && команда && команда
```

Эти команды допустимы только на Linux-сервере по SSH, но не в Windows PowerShell.

Если команда предназначена для сервера, явно написать:

```text
Эту команду нужно выполнить на сервере SSH, не в PowerShell.
```

---

## 18. Серверные команды

На сервере Linux разрешены Linux-команды.

Примеры:

```bash
cd /home/s/spugovxsim/public_html/demoERP
/usr/bin/php8.4 -v
/usr/bin/php8.4 -l public/index.php
ls -la
tail -50 storage/logs/app.log
```

Не выполнять серверные команды в Windows PowerShell.

---

## 19. MAX-уведомления

MAX-уведомления обязательны в двух случаях:

```text
1. Работа завершена.
2. Требуется участие Владимира.
```

### 19.1. Запрещённый URL

Запрещено вызывать:

```text
http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php
```

Это PHP-библиотека функций, а не публичный endpoint. Даже если она возвращает `200 OK`, сообщение не отправляется.

### 19.2. Правильный endpoint

Использовать только:

```text
http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php
```

Параметры:

```text
key
text
format=markdown
```

Ключ не коммитить в Git. В документации использовать placeholder:

```text
<MAX_NOTIFY_KEY>
```

### 19.3. PowerShell-вызов MAX

Команды выполнять отдельно.

Команда 1:

```powershell
$Text = [uri]::EscapeDataString("Demo ERP: задача завершена. Push выполнен, GitHub Actions зелёный, production проверен.")
```

Команда 2:

```powershell
Invoke-WebRequest "http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php?key=<MAX_NOTIFY_KEY>&text=$Text&format=markdown" -UseBasicParsing
```

Если MAX не отправился с первого раза:

```text
не зависать
не повторять бесконечно
зафиксировать ошибку
продолжить финальный отчёт
```

---

## 20. Проверки до commit

Обязательно выполнить:

```powershell
composer validate
composer dump-autoload
php -l public\index.php
```

Проверить все изменённые PHP-файлы:

```powershell
php -l path\to\changed-file.php
```

Запустить локально:

```powershell
php -S localhost:8000 -t public
```

Проверить:

```powershell
curl.exe -s -k -L -w "%{http_code}" "http://localhost:8000/" -o NUL
```

Если задача добавляет модуль, проверить URL модуля, указанный в промте задачи.

---

## 21. Проверки после push

Дождаться зелёного GitHub Actions.

Проверить production:

```powershell
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/"
```

Если задача добавляет модуль, проверить production URL модуля, указанный в промте задачи.

Также проверить страницу в браузере.

Если GitHub Actions красный — задача не завершена.

---

## 22. Правило завершения задачи

Задача завершена только если:

```text
код написан
локальные проверки пройдены
php -l проходит
composer validate проходит
composer dump-autoload проходит
commit сделан
push сделан
GitHub Actions зелёный
production работает
нет 500
нет PHP Fatal error
MAX-уведомление отправлено или ошибка MAX явно зафиксирована
```

Если что-то сломалось — не скрывать. Сообщить:

```text
что изменено
что проверено
где ошибка
какой лог/вывод команды
что нужно от Владимира
```

---

## 23. Финальный отчёт кодера

Финальный отчёт обязан содержать:

```text
1. Что сделано.
2. Какие файлы изменены.
3. Какие проверки выполнены.
4. Commit hash.
5. GitHub Actions status.
6. Production URL.
7. MAX notification status.
8. Что требует участия Владимира, если есть.
```

Если задача по переносу старого функционала, дополнительно:

```text
1. Какие элементы старого экрана перенесены.
2. Какие фильтры/поиск/действия работают.
3. Что не удалось перенести и почему.
4. Какие дополнительные файлы/таблицы нужны, если есть.
```

---

## 24. Рекомендация по coding agent

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

## 25. Главное правило

```text
Не фантазировать.
Не проектировать новый экран вместо переноса.
Текущую задачу писать в промте, а не в PROJECT_RULES.md.
Не использовать Linux-команды в Windows PowerShell.
Не использовать curl без .exe.
Не вызывать Support/max_notify.php как endpoint.
Всегда перечитывать PROJECT_RULES.md перед началом новой задачи.
```
