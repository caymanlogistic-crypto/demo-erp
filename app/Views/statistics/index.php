<?php
/**
 * Контент страницы «Статистика вывозов» (TransportERP shell).
 *
 * Переменные:
 *   $period    — week|month
 *   $dateType  — delivery|pickup
 *   $dateFrom  — YYYY-MM-DD
 *   $dateTo    — YYYY-MM-DD
 *   $warning   — string|null
 *   $summary   — [periods_count, requests_total, flights_total, weight_total_kg, ...]
 *   $rows      — array of period rows
 *   $service   — StatisticsService
 */

use App\Modules\Statistics\Services\StatisticsService;

/** @var string $period */
/** @var string $dateType */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string|null $warning */
/** @var array $summary */
/** @var array $rows */
/** @var StatisticsService $service */
?>
<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-eyebrow">Статистика / Вывозы по периодам</div>
        <div class="page-title">Статистика вывозов</div>
        <div class="page-summary"><span>Количество заявок и масса по выбранной дате события</span></div>
    </div>
</div>

<!-- Filter bar -->
<form class="statistics-filters" data-statistics-filter-form method="get" action="">
    <input type="hidden" name="module" value="statistics">

    <div class="filter-field" style="width: 140px;">
        <label for="periodSelect">Группировка</label>
        <select id="periodSelect" name="period" style="height: 24px; padding: 0 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--line-soft); background: var(--surface-field); border-radius: 2px;">
            <option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>По неделям</option>
            <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>По месяцам</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Произвольный период</option>
        </select>
    </div>

    <div class="filter-field" style="width: 130px;">
        <label for="dateTypeSelect">Дата события</label>
        <select id="dateTypeSelect" name="date_type" style="height: 24px; padding: 0 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--line-soft); background: var(--surface-field); border-radius: 2px;">
            <option value="delivery" <?= $dateType === 'delivery' ? 'selected' : '' ?>>Доставка</option>
            <option value="pickup"   <?= $dateType === 'pickup'   ? 'selected' : '' ?>>Вывоз</option>
        </select>
    </div>

    <div class="filter-field statistics-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateFrom">Дата с</label>
        <input type="date" id="filterDateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-field statistics-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateTo">Дата по</label>
        <input type="date" id="filterDateTo" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-actions" data-custom-period-action<?= $period !== 'custom' ? ' hidden' : '' ?>>
        <button type="submit" class="btn btn-toolbar">Применить</button>
        <a class="btn btn-toolbar" href="?module=statistics" style="text-decoration: none;">Сбросить</a>
    </div>
</form>

<?php if ($warning !== null): ?>
<div class="form-alert alert-warning" style="margin: 0;"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Table card -->
<div class="table-card" style="flex: 1; min-height: 0;">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= (int) ($summary['periods_count'] ?? 0) ?></b>
                <span class="summary-main-unit">периодов</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Заявок</span>
                <b><?= number_format((int) ($summary['requests_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-flights">
                <span class="summary-chip-dot"></span>
                <span>Рейсов</span>
                <b><?= number_format((int) ($summary['flights_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Масса</span>
                <b><?= number_format((int) ($summary['weight_total_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
            <span class="summary-chip summary-chip-avg">
                <span class="summary-chip-dot"></span>
                <span>Средняя заявка</span>
                <b><?= number_format((int) ($summary['avg_request_weight_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
        </div>
    </div>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>
        <table class="table statistics-table">
            <colgroup>
                <col class="col-stat-period">
                <col class="col-stat-requests">
                <col class="col-stat-flights">
                <col class="col-stat-weight">
                <col class="col-stat-avg-request">
                <col class="col-stat-avg-flight">
            </colgroup>
            <thead>
                <tr>
                    <th>ПЕРИОД</th>
                    <th style="text-align: right;">ЗАЯВОК</th>
                    <th style="text-align: right;">РЕЙСОВ</th>
                    <th style="text-align: right;">МАССА, КГ</th>
                    <th style="text-align: right;">СР. ЗАЯВКА, КГ</th>
                    <th style="text-align: right;">СР. РЕЙС, КГ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= number_format((int) $row['requests_count'], 0, '.', ' ') ?></td>
                    <td class="cell-num"><?= number_format((int) $row['flights_count'], 0, '.', ' ') ?></td>
                    <td class="cell-weight"><?= number_format((int) $row['total_weight_kg'], 0, '.', ' ') ?> кг</td>
                    <td class="cell-weight"><?= number_format((int) $row['avg_request_weight_kg'], 0, '.', ' ') ?> кг</td>
                    <td class="cell-weight"><?= number_format((int) $row['avg_flight_weight_kg'], 0, '.', ' ') ?> кг</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
            Нет данных за выбранный период
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('[data-statistics-filter-form]');
    var periodSelect = document.getElementById('periodSelect');
    var dateTypeSelect = document.getElementById('dateTypeSelect');
    var customFields = document.querySelectorAll('[data-custom-period-field]');
    var customActions = document.querySelectorAll('[data-custom-period-action]');

    function isCustom() {
        return periodSelect && periodSelect.value === 'custom';
    }

    function updateCustomVisibility() {
        var visible = isCustom();

        customFields.forEach(function (el) {
            el.hidden = !visible;
            el.classList.toggle('is-hidden', !visible);

            var input = el.querySelector('input, select, textarea');
            if (input) {
                input.disabled = !visible;
            }
        });

        customActions.forEach(function (el) {
            el.hidden = !visible;
            el.classList.toggle('is-hidden', !visible);
        });
    }

    function submitAuto() {
        if (!form || isCustom()) {
            return;
        }

        var dateFrom = form.querySelector('[name="date_from"]');
        var dateTo = form.querySelector('[name="date_to"]');

        if (dateFrom) {
            dateFrom.disabled = true;
        }

        if (dateTo) {
            dateTo.disabled = true;
        }

        form.submit();
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            updateCustomVisibility();

            if (!isCustom()) {
                submitAuto();
            }
        });
    }

    if (dateTypeSelect) {
        dateTypeSelect.addEventListener('change', function () {
            if (!isCustom()) {
                submitAuto();
            }
        });
    }

    // Initial visibility is already set by server-side hidden/disabled attributes.
    // But ensure JS state matches on first load:
    updateCustomVisibility();
})();
</script>
