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

---

## 26. Строгие правила MAX key через локальный `.env.local`

Этот раздел имеет приоритет над разделом `19. MAX-уведомления`.

Реальный `MAX_NOTIFY_KEY` запрещено хранить в Git и запрещено вписывать в файлы проекта, которые коммитятся.

Запрещено записывать реальный MAX key в:

```text
PROJECT_RULES.md
README.md
.env.example
PHP-файлы
JS-файлы
CSS-файлы
GitHub-tracked файлы
коммиты
логи
финальные отчёты
```

Разрешённое место для локальной разработки:

```text
C:\demoERP\.env.local
```

Формат файла:

```env
MAX_NOTIFY_KEY=<real_key>
```

Файл `.env.local` обязан быть добавлен в `.gitignore`.

Если `.env.local` отсутствует или ключ недоступен, кодер обязан написать:

```text
MAX status: BLOCKED, real key unavailable.
```

Кодер не имеет права использовать placeholder как реальный ключ.

Запрещённые значения ключа:

```text
<MAX_NOTIFY_KEY>
CHANGE_ME
YOUR_KEY
placeholder
```

Если endpoint вернул:

```json
{"success":false,"message":"Forbidden"}
```

это означает:

```text
MAX НЕ отправлен.
```

В таком случае нельзя писать:

```text
MAX отправлен
задача полностью завершена
DONE
```

Нужно писать:

```text
MAX: BLOCKED / FAILED
Overall: PARTIAL, not DONE
```

---

## 27. Как кодеру отправлять MAX из `cmd.exe`

Если кодер работает в Roo Code через `cmd.exe`, использовать только cmd-compatible команды.

Endpoint:

```text
http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php
```

Запрещённый endpoint:

```text
http://mg-log.ru/fregat/feo/map_files/Support/max_notify.php
```

Пример чтения ключа из `.env.local` в `cmd.exe`:

```cmd
for /f "tokens=2 delims==" %A in ('findstr /B "MAX_NOTIFY_KEY=" .env.local') do set MAX_NOTIFY_KEY=%A
```

Проверка, что переменная установлена:

```cmd
echo %MAX_NOTIFY_KEY%
```

Если вывод пустой — MAX отправлять нельзя.

Пример отправки сообщения через `curl.exe`:

```cmd
curl.exe -k -s "http://spugovxsim.temp.swtest.ru/fregat/feo/notify_max.php?key=%MAX_NOTIFY_KEY%&text=Demo%20ERP%3A%20task%20completed%20-%20production%20checked&format=markdown"
```

Ожидаемый успешный ответ:

```json
{"success":true}
```

Если ответ не содержит `"success":true`, MAX не считается отправленным.

---

## 28. Запрет ложного завершения задачи

Кодер не имеет права писать `Задача завершена`, если обязательные проверки не выполнены.

Финальный статус должен быть одним из:

```text
DONE — все проверки выполнены, production clean, SSH clean, MAX sent.
PARTIAL — код сделан и push выполнен, но часть проверок недоступна.
BLOCKED — невозможно завершить задачу из-за отсутствия доступа/ключа/ошибки окружения.
FAILED — проверка показала ошибку.
```

Запрещено писать `DONE`, если:

```text
SSH checks не выполнены;
production HTML content не проверен;
server logs не проверены;
GitHub Actions не проверен;
MAX не отправлен;
MAX вернул Forbidden;
использовался placeholder вместо реального ключа;
production URL содержит Fatal error / Warning / Notice / Ошибка / Object of class / Stack trace.
```

Если SSH недоступен, кодер обязан написать:

```text
SSH check: BLOCKED, local SSH key/access unavailable.
Overall: PARTIAL, not DONE.
```

Если MAX key недоступен, кодер обязан написать:

```text
MAX: BLOCKED, real key unavailable.
Overall: PARTIAL, not DONE.
```

---

## 29. Обязательный финальный блок отчёта

Каждый финальный отчёт должен заканчиваться блоком:

```text
FINAL STATUS:
- Code changes:
- Commit:
- Push:
- GitHub Actions:
- Production HTTP:
- Production HTML error check:
- SSH check:
- Server logs:
- MAX:
- Overall:
```

Пример полного успешного завершения:

```text
FINAL STATUS:
- Code changes: done
- Commit: abc1234
- Push: done
- GitHub Actions: green
- Production HTTP: 200 OK
- Production HTML error check: clean
- SSH check: clean
- Server logs: clean
- MAX: sent
- Overall: DONE
```

Пример частичного завершения:

```text
FINAL STATUS:
- Code changes: done
- Commit: abc1234
- Push: done
- GitHub Actions: not checked
- Production HTTP: checked
- Production HTML error check: clean
- SSH check: BLOCKED, local SSH key unavailable
- Server logs: not checked
- MAX: BLOCKED, real key unavailable
- Overall: PARTIAL, not DONE
```

Главное правило:

```text
Если проверка не выполнена — это не успех.
Если ключа нет — не использовать placeholder.
Если SSH недоступен — не писать DONE.
```

---

## 30. Правило для таблиц с infinite scroll и summary/counters

Если таблица использует AJAX, pagination, limit/offset или infinite scroll, все summary/counters должны считаться на сервере по полному текущему набору данных, а не по DOM и не по первой загруженной странице.

Запрещено считать summary/counters:

```text
по текущим строкам DOM
по первой странице infinite scroll
по текущим 50/100 загруженным строкам
только в JavaScript без серверного total
```

Правильно:

```text
1. Основной SQL/filter строит полный набор.
2. Для таблицы применяется limit/offset.
3. Для total/summary/status counters limit/offset НЕ применяется.
4. AJAX response возвращает total и counters отдельно.
```

Если пользователь применил фильтры/чекбоксы, summary/counters должны считаться по тому же фильтру, что и total.

Если поиск по содержимому работает только client-side, кодер обязан явно написать в отчёте, участвует ли этот поиск в серверных summary/counters.

---

## 31. Правило production HTML check

Production check не может ограничиваться HTTP `200 OK`.

Обязательно проверить содержимое HTML на ошибки:

```cmd
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Object of class Closure"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Fatal error"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Warning"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Notice"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Ошибка"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Stack trace"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Parse error"
curl.exe -k -s -L "https://mg-log.ru/demoERP/public/?module=feo" | findstr /C:"Deprecated"
```

Если любая из этих команд нашла ошибку — production check не пройден.

Также нужно проверить наличие ключевых элементов страницы, указанных в текущем промте задачи.

---

## 32. Правило shell-среды Roo Code

Перед выполнением команд Roo/Coding Agent обязан понять, в какой оболочке он работает.

Если работает `cmd.exe`, использовать cmd-compatible команды:

```text
cd
dir
type
findstr
git
composer
php
curl.exe
ssh
```

Если работает Windows PowerShell, использовать PowerShell-compatible команды:

```text
Get-Location
Get-Content
Select-String
Get-ChildItem
Invoke-WebRequest
curl.exe
```

Запрещено в `cmd.exe`:

```text
Get-Location
Select-String
Get-Content
Get-ChildItem
Invoke-WebRequest
```

Запрещено в Windows PowerShell:

```text
curl без .exe
grep
head
tail
sed
awk
Linux-style command chains
```

Если shell-команда не выполнилась из-за неправильной оболочки, кодер обязан остановиться, определить shell и продолжить только совместимыми командами.
