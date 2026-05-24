<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Demo ERP', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/demoERP/public/assets/css/app.css">
</head>
<body>
<div class="app">
    <!-- ============================================================
         TOPBAR
         ============================================================ -->
    <div class="topbar">
        <div class="brand-mark">
            <div class="logo"></div>
        </div>
        <div class="brand">TransportERP</div>
        <div class="crumbs">
            <span>Основное</span>
            <span class="sep">›</span>
            <b><?= htmlspecialchars($pageTitle ?? 'Заявки ФЭО', ENT_QUOTES, 'UTF-8') ?></b>
        </div>
        <div class="top-actions">
            <div class="top-search">Поиск по системе…</div>
            <div class="user">
                <div class="avatar">LS</div>
                <div class="user-info">
                    <strong>Логист</strong>
                    <span>Оператор</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SIDEBAR
         ============================================================ -->
    <div class="sidebar">
        <button class="nav active" title="Основное">□</button>
        <button class="nav" title="Рейсы">⇌</button>
        <button class="nav" title="Водители">⊡</button>
        <button class="nav" title="Транспорт">⊞</button>
        <div class="nav-grow"></div>
        <button class="nav" title="Настройки">⚙</button>
    </div>

    <!-- ============================================================
         PAGE CONTENT
         ============================================================ -->
    <div class="page">
        <?= $content ?? '' ?>
    </div>
</div>
</body>
</html>
