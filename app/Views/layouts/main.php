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
         TOPBAR — dark navigation bar
         ============================================================ -->
    <header class="topbar topbar-main">
        <div class="topbar-brand">TransportERP</div>

        <div class="topbar-divider"></div>

        <?php
        $currentModule = $pageModule ?? 'feo';
        $navItems = [
            'home'       => ['label' => 'Общая информация',  'href' => '/demoERP/public/?module=home'],
            'feo'        => ['label' => 'Доступные заявки',  'href' => '/demoERP/public/?module=feo'],
            'flights'    => ['label' => 'Планирование и вывоз', 'href' => '/demoERP/public/?module=flights'],
            'statistics' => ['label' => 'Статистика',         'href' => '/demoERP/public/?module=statistics'],
            'reports'    => ['label' => 'Отчетность по регионам', 'href' => '/demoERP/public/?module=reports'],
        ];
        ?>
        <nav class="main-topnav" aria-label="Основная навигация">
            <?php foreach ($navItems as $mod => $item):
                $isActive = ($mod === $currentModule);
                $href = $item['href'];
            ?>
            <a class="main-topnav-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="topbar-spacer"></div>

        <div class="top-actions">
            <div class="user">
                <div class="avatar">LS</div>
                <div class="user-info">
                    <strong>Логист</strong>
                    <span>Оператор</span>
                </div>
            </div>
        </div>
    </header>

    <!-- ============================================================
         PAGE CONTENT
         ============================================================ -->
    <div class="page">
        <?= $content ?? '' ?>
    </div>
</div>
</body>
</html>
