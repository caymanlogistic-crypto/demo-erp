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
        <?php
        $currentModule = $pageModule ?? 'feo';
        if ($currentModule === 'flights'):
        ?>
        <div class="crumbs">
            <span>Рейсы</span>
            <span class="sep">›</span>
            <b>Таймлайн рейсов</b>
        </div>
        <nav class="top-subnav" aria-label="Подразделы">
            <a class="top-subnav-link active" href="/demoERP/public/?module=flights">Таймлайн рейсов</a>
        </nav>
        <?php elseif ($currentModule === 'statistics'): ?>
        <div class="crumbs">
            <span>Статистика</span>
            <span class="sep">›</span>
            <b>Вывозы по периодам</b>
        </div>
        <nav class="top-subnav" aria-label="Подразделы">
            <a class="top-subnav-link active" href="/demoERP/public/?module=statistics">Вывозы по периодам</a>
        </nav>
        <?php elseif ($currentModule === 'reports'): ?>
        <div class="crumbs">
            <span>Отчётность</span>
            <span class="sep">›</span>
            <b>Сводные отчёты</b>
        </div>
        <nav class="top-subnav" aria-label="Подразделы">
            <a class="top-subnav-link active" href="/demoERP/public/?module=reports">Сводные отчёты</a>
        </nav>
        <?php else: ?>
        <div class="crumbs">
            <span>Основное</span>
            <span class="sep">›</span>
            <b>База заявок ФГИС</b>
        </div>
        <nav class="top-subnav" aria-label="Подразделы">
            <a class="top-subnav-link active" href="/demoERP/public/?module=feo">База заявок ФГИС</a>
            <a class="top-subnav-link disabled" href="#" aria-disabled="true">Заявки регионы</a>
            <a class="top-subnav-link disabled" href="#" aria-disabled="true">Планирование и вывоз</a>
        </nav>
        <?php endif; ?>
        <div class="top-actions">
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
         SIDEBAR — rail navigation
         ============================================================ -->
    <div class="sidebar">
        <a class="rail-item<?= ($currentModule === 'feo') ? ' active' : '' ?>" href="/demoERP/public/?module=feo" title="Основное">
            <span class="rail-label">О</span>
        </a>
        <a class="rail-item<?= ($currentModule === 'flights') ? ' active' : '' ?>" href="/demoERP/public/?module=flights" title="Рейсы">
            <span class="rail-label">Р</span>
        </a>
        <a class="rail-item<?= ($currentModule === 'statistics') ? ' active' : '' ?>" href="/demoERP/public/?module=statistics" title="Статистика">
            <span class="rail-label">С</span>
        </a>
        <a class="rail-item<?= ($currentModule === 'reports') ? ' active' : '' ?>" href="/demoERP/public/?module=reports" title="Отчётность">
            <span class="rail-label">ОТ</span>
        </a>
        <a class="rail-item disabled" href="#" aria-disabled="true" title="Карта">
            <span class="rail-label">К</span>
        </a>
        <a class="rail-item disabled" href="#" aria-disabled="true" title="Сервис">
            <span class="rail-label">E</span>
        </a>
        <div class="nav-grow"></div>
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
