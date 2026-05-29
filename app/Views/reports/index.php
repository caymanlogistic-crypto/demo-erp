<?php
/**
 * Контент страницы «Отчётность» (TransportERP shell).
 *
 * Переменные:
 *   $period       — week|month|custom
 *   $dateType     — delivery|pickup
 *   $dimension    — fo|region|status
 *   $chartMetric  — requests|weight
 *   $dateFrom     — YYYY-MM-DD
 *   $dateTo       — YYYY-MM-DD
 *   $warning      — string|null
 *   $summary      — [rows_total, requests_total, weight_total_kg, avg_request_kg]
 *   $rows         — array of report rows
 *   $unmatched    — int
 *   $chartData    — [enabled, type, metric, ...]
 *   $service      — ReportsService
 */

use App\Modules\Reports\Services\ReportsService;

/** @var string $period */
/** @var string $dateType */
/** @var string $dimension */
/** @var string $chartMetric */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var string|null $warning */
/** @var array $summary */
/** @var array $rows */
/** @var int $unmatched */
/** @var array $chartData */
/** @var ReportsService $service */

$totalRows = $summary['rows_total'] ?? count($rows);
$dateTypeLabel = $dateType === 'pickup' ? 'Вывоз' : 'Доставка';

$chartTitle = match ($dimension) {
    'fo'     => 'Динамика по федеральным округам',
    'region' => 'Топ регионов',
    'status' => 'Распределение по статусам',
};
$chartSubtitle = 'Заявки и вес по выбранному основанию';
?>
<!-- reports page: dimension=<?= htmlspecialchars($dimension, ENT_QUOTES, 'UTF-8') ?> period=<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?> dateType=<?= htmlspecialchars($dateType, ENT_QUOTES, 'UTF-8') ?> chartMetric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?> rows=<?= $totalRows ?> unmatched=<?= (int) $unmatched ?> -->

<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-title">Отчётность</div>
        <div class="page-summary"><span>Сводные отчёты по заявкам, статусам, регионам и федеральным округам</span></div>
    </div>
</div>

<!-- Filter bar (как Statistics) -->
<form class="reports-filters" data-reports-filter-form method="get" action="">
    <input type="hidden" name="module" value="reports">
    <input type="hidden" name="chart_metric" id="chartMetricInput" value="<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?>">

    <div class="filter-field" style="width: 140px;">
        <label for="periodSelect">Группировка</label>
        <select id="periodSelect" name="period">
            <option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>По неделям</option>
            <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>По месяцам</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Произвольный период</option>
        </select>
    </div>

    <div class="filter-field" style="width: 130px;">
        <label for="dateTypeSelect">Строить по</label>
        <select id="dateTypeSelect" name="date_type">
            <option value="delivery" <?= $dateType === 'delivery' ? 'selected' : '' ?>>Доставка</option>
            <option value="pickup"   <?= $dateType === 'pickup'   ? 'selected' : '' ?>>Вывоз</option>
        </select>
    </div>

    <div class="filter-field" style="width: 140px;">
        <label for="dimensionSelect">Разрез</label>
        <select id="dimensionSelect" name="dimension">
            <option value="fo"     <?= $dimension === 'fo'     ? 'selected' : '' ?>>По ФО</option>
            <option value="region" <?= $dimension === 'region' ? 'selected' : '' ?>>По регионам</option>
            <option value="status" <?= $dimension === 'status' ? 'selected' : '' ?>>По статусам</option>
        </select>
    </div>

    <div class="filter-field reports-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateFrom">Дата с</label>
        <input type="date" id="filterDateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-field reports-date-range-field" data-custom-period-field<?= $period !== 'custom' ? ' hidden' : '' ?> style="width: 150px;">
        <label for="filterDateTo">Дата по</label>
        <input type="date" id="filterDateTo" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"<?= $period !== 'custom' ? ' disabled' : '' ?>>
    </div>

    <div class="filter-actions" data-custom-period-action<?= $period !== 'custom' ? ' hidden' : '' ?>>
        <button type="submit" class="btn btn-toolbar">Применить</button>
        <a class="btn btn-toolbar" href="?module=reports" style="text-decoration: none;">Сбросить</a>
    </div>
</form>

<?php if ($warning !== null): ?>
<div class="form-alert alert-warning" style="margin: 0;"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Table card -->
<div class="table-card reports-table-card" style="flex: 1; min-height: 0;">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= $totalRows ?></b>
                <span class="summary-main-unit">строк</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Заявок</span>
                <b><?= number_format((int) ($summary['requests_total'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Вес</span>
                <b><?= number_format((int) ($summary['weight_total_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
            <span class="summary-chip summary-chip-avg">
                <span class="summary-chip-dot"></span>
                <span>Средняя заявка</span>
                <b><?= ($summary['avg_request_kg'] ?? 0) > 0 ? number_format((int) ($summary['avg_request_kg'] ?? 0), 0, '.', ' ') . ' кг' : '—' ?></b>
            </span>
        </div>
    </div>

    <!-- Chart panel -->
    <section class="reports-chart-panel" data-reports-chart>
        <div class="reports-chart-inner">
            <div class="reports-chart-head">
                <div class="reports-chart-heading">
                    <div class="reports-chart-title"><?= htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="reports-chart-subtitle"><?= htmlspecialchars($chartSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="reports-chart-switch" role="group" aria-label="Метрика графика">
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'weight' ? ' active' : '' ?>" data-chart-metric="weight" aria-pressed="<?= $chartMetric === 'weight' ? 'true' : 'false' ?>">Вес</button>
                    <button type="button" class="chart-metric-btn<?= $chartMetric === 'requests' ? ' active' : '' ?>" data-chart-metric="requests" aria-pressed="<?= $chartMetric === 'requests' ? 'true' : 'false' ?>">Заявки</button>
                </div>
            </div>

            <div class="reports-chart-body">
                <!-- chart: type=<?= htmlspecialchars($chartData['type'] ?? 'bar', ENT_QUOTES, 'UTF-8') ?> metric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?> -->
                <svg class="reports-chart-svg" viewBox="0 0 800 220" preserveAspectRatio="none" role="img" aria-label="График отчётности"<?= $chartData['enabled'] ? '' : ' hidden' ?>></svg>

                <div class="reports-chart-empty"<?= $chartData['enabled'] ? ' hidden' : '' ?>>
                    <?= !empty($rows) ? 'Нет данных для графика.' : 'Нет данных для отображения.' ?>
                </div>
            </div>
        </div>

        <script type="application/json" id="reportsChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>

        <?php if ($dimension === 'fo'): ?>
        <!-- ============================================================
             MATRIX TABLE: period × district
             ============================================================ -->
        <?php
        $districts   = $chartData['districtTitles'] ?? $chartData['series'] ?? [];
        $totals      = $data['totals'] ?? [];
        $districtTitles = $data['district_titles'] ?? [];
        $hasUnmatched = $data['has_unmatched'] ?? false;
        $numDistricts = count($districtTitles);
        $colSpanRequests = $numDistricts;
        $colSpanWeight   = $numDistricts;
        $unmatchedDiagnostics = $data['unmatched_regions'] ?? [];
        ?><!-- reports districts: unmatched=<?= (int) $unmatched ?> -->
        <table class="table reports-table reports-matrix-table">
            <colgroup>
                <col class="col-matrix-period">
                <?php for ($i = 0; $i < $numDistricts; $i++): ?>
                <col class="col-matrix-district">
                <?php endfor; ?>
                <?php for ($i = 0; $i < $numDistricts; $i++): ?>
                <col class="col-matrix-district">
                <?php endfor; ?>
            </colgroup>
            <thead>
                <tr class="reports-matrix-group-head">
                    <th class="th-period" rowspan="2">Период</th>
                    <th class="th-group" colspan="<?= $colSpanRequests ?>">Заявки</th>
                    <th class="th-group" colspan="<?= $colSpanWeight ?>">Вес</th>
                </tr>
                <tr class="reports-matrix-sub-head">
                    <?php foreach ($districtTitles as $dk => $dt): ?>
                    <th class="th-district" title="<?= htmlspecialchars($dk === '_unmatched' ? 'Федеральный округ не определён' : $service->districtFullTitle($dk) ?? $dt, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dt, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($districtTitles as $dk => $dt): ?>
                    <th class="th-district" title="<?= htmlspecialchars($dk === '_unmatched' ? 'Федеральный округ не определён' : $service->districtFullTitle($dk) ?? $dt, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dt, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $rPeriodLabel = $row['period_label'] ?? '';
                ?>
                <tr>
                    <td class="td-period"><?= htmlspecialchars($rPeriodLabel, ENT_QUOTES, 'UTF-8') ?></td>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $row['requests_by_district'][$dk] ?? 0;
                    ?>
                    <td class="td-cell-num"><?= $val > 0 ? number_format($val, 0, '.', ' ') : '—' ?></td>
                    <?php endforeach; ?>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $row['weight_by_district'][$dk] ?? 0;
                    ?>
                    <td class="td-cell-weight"><?= $val > 0 ? number_format($val, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <!-- Итоговая строка -->
            <?php if (!empty($totals)): ?>
            <tfoot>
                <tr class="reports-total-row">
                    <td class="td-period">Итого</td>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $totals['requests_by_district'][$dk] ?? 0;
                    ?>
                    <td class="td-cell-num"><?= $val > 0 ? number_format($val, 0, '.', ' ') : '—' ?></td>
                    <?php endforeach; ?>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $totals['weight_by_district'][$dk] ?? 0;
                    ?>
                    <td class="td-cell-weight"><?= $val > 0 ? number_format($val, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <?php elseif ($dimension === 'region'): ?>
        <!-- ============================================================
             LONG TABLE: Period | Region | FO | Заявки | Вес | Ср.заявка | Доля заявок | Доля веса
             ============================================================ -->
        <table class="table reports-table">
            <colgroup>
                <col class="col-reports-period">
                <col class="col-reports-region">
                <col class="col-reports-fo">
                <col class="col-reports-requests">
                <col class="col-reports-weight">
                <col class="col-reports-avg">
                <col class="col-reports-share-r">
                <col class="col-reports-share-w">
            </colgroup>
            <thead>
                <tr>
                    <th>Период</th>
                    <th>Регион</th>
                    <th>ФО</th>
                    <th class="cell-num">Заявки</th>
                    <th class="cell-weight">Вес, кг</th>
                    <th class="cell-weight">Ср. заявка, кг</th>
                    <th class="cell-num">Доля заявок</th>
                    <th class="cell-num">Доля веса</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalWeightSum = 0.0;
                $totalRequestsSum = 0;
                foreach ($rows as $r) { $totalWeightSum += $r['weight_kg'] ?? 0; $totalRequestsSum += $r['requests'] ?? 0; }
                foreach ($rows as $row):
                    $rWeight = $row['weight_kg'] ?? 0;
                    $rRequests = $row['requests'] ?? 0;
                    $rAvg = $row['avg_request_kg'] ?? 0;
                    $shareR = $totalRequestsSum > 0 ? round(($rRequests / $totalRequestsSum) * 100, 1) : 0;
                    $shareW = $totalWeightSum > 0 ? round(($rWeight / $totalWeightSum) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['period_label'] ?? $service->formatPeriodLabel($period === 'month' ? date('Y-m') : ($period === 'week' ? date('o-\WW') : 'all'), $period, $dateFrom, $dateTo), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="report-cell-region" title="<?= htmlspecialchars($row['region'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="report-region-name"><?= htmlspecialchars($row['region'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($row['district_short'])): ?>
                        <span class="report-muted"><?= htmlspecialchars($row['district_short'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="report-cell-district"><?= htmlspecialchars($row['district_short'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= ReportsService::formatCount($rRequests) ?></td>
                    <td class="cell-weight"><?= ReportsService::formatWeight($rWeight) ?></td>
                    <td class="cell-weight"><?= $rAvg > 0 ? number_format($rAvg, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <td class="cell-num"><?= $shareR > 0 ? number_format($shareR, 1, ',', '') . ' %' : '—' ?></td>
                    <td class="cell-num"><?= $shareW > 0 ? number_format($shareW, 1, ',', '') . ' %' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
        <!-- ============================================================
             LONG TABLE: Period | Статус | Заявки | Вес | Ср.заявка | Доля заявок | Доля веса
             ============================================================ -->
        <table class="table reports-table">
            <colgroup>
                <col class="col-reports-period">
                <col class="col-reports-status">
                <col class="col-reports-requests">
                <col class="col-reports-weight">
                <col class="col-reports-avg">
                <col class="col-reports-share-r">
                <col class="col-reports-share-w">
            </colgroup>
            <thead>
                <tr>
                    <th>Период</th>
                    <th>Статус</th>
                    <th class="cell-num">Заявки</th>
                    <th class="cell-weight">Вес, кг</th>
                    <th class="cell-weight">Ср. заявка, кг</th>
                    <th class="cell-num">Доля заявок</th>
                    <th class="cell-num">Доля веса</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalWeightSum = 0.0;
                $totalRequestsSum = 0;
                foreach ($rows as $r) { $totalWeightSum += $r['weight_kg'] ?? 0; $totalRequestsSum += $r['requests'] ?? 0; }
                foreach ($rows as $row):
                    $rWeight = $row['weight_kg'] ?? 0;
                    $rRequests = $row['requests'] ?? 0;
                    $rAvg = $row['avg_request_kg'] ?? 0;
                    $shareR = $totalRequestsSum > 0 ? round(($rRequests / $totalRequestsSum) * 100, 1) : 0;
                    $shareW = $totalWeightSum > 0 ? round(($rWeight / $totalWeightSum) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($service->formatPeriodLabel($period === 'month' ? date('Y-m') : ($period === 'week' ? date('o-\WW') : 'all'), $period, $dateFrom, $dateTo), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="report-cell-district"><?= htmlspecialchars($row['status_label'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="cell-num"><?= ReportsService::formatCount($rRequests) ?></td>
                    <td class="cell-weight"><?= ReportsService::formatWeight($rWeight) ?></td>
                    <td class="cell-weight"><?= $rAvg > 0 ? number_format($rAvg, 0, '.', ' ') . ' кг' : '—' ?></td>
                    <td class="cell-num"><?= $shareR > 0 ? number_format($shareR, 1, ',', '') . ' %' : '—' ?></td>
                    <td class="cell-num"><?= $shareW > 0 ? number_format($shareW, 1, ',', '') . ' %' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>

        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
            Нет данных для отображения
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('[data-reports-filter-form]');
    var periodSelect = document.getElementById('periodSelect');
    var dateTypeSelect = document.getElementById('dateTypeSelect');
    var dimensionSelect = document.getElementById('dimensionSelect');
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
        if (!form || isCustom()) return;

        var dateFrom = form.querySelector('[name="date_from"]');
        var dateTo = form.querySelector('[name="date_to"]');

        if (dateFrom) dateFrom.disabled = true;
        if (dateTo) dateTo.disabled = true;

        form.submit();
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            updateCustomVisibility();
            if (!isCustom()) submitAuto();
        });
    }

    if (dateTypeSelect) {
        dateTypeSelect.addEventListener('change', function () {
            if (!isCustom()) submitAuto();
        });
    }

    if (dimensionSelect) {
        dimensionSelect.addEventListener('change', function () {
            if (!isCustom()) submitAuto();
        });
    }

    updateCustomVisibility();
})();
</script>

<script>
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    var CHART_W = 1200;

    var chartPanel = document.querySelector('[data-reports-chart]');
    var jsonEl = document.getElementById('reportsChartData');
    var chartData = null;

    if (jsonEl) {
        try { chartData = JSON.parse(jsonEl.textContent); } catch (e) { chartData = null; }
    }

    if (!chartPanel || !chartData || !chartData.enabled) return;

    var svg = chartPanel.querySelector('.reports-chart-svg');
    var emptyEl = chartPanel.querySelector('.reports-chart-empty');
    var switchBtns = chartPanel.querySelectorAll('.chart-metric-btn');
    var chartMetricInput = document.getElementById('chartMetricInput');
    var currentMetric = chartMetricInput ? chartMetricInput.value : 'requests';

    // ===================================================================
    //  LINE PALETTE — спокойные цвета для 8 ФО + Н/Д
    // ===================================================================
    var LINE_PALETTE = {
        cfo:  { stroke: '#4A6A7A', label: 'ЦФО' },
        szfo: { stroke: '#8B6F5E', label: 'СЗФО' },
        yfo:  { stroke: '#7A8A5A', label: 'ЮФО' },
        skfo: { stroke: '#8A6A8E', label: 'СКФО' },
        pfo:  { stroke: '#5A8A7A', label: 'ПФО' },
        urfo: { stroke: '#8A5A50', label: 'УФО' },
        sfo:  { stroke: '#6A7A8E', label: 'СФО' },
        dfo:  { stroke: '#8A7A4E', label: 'ДФО' }
    };
    var FALLBACK_COLORS = [
        '#4A6A7A', '#8B6F5E', '#7A8A5A', '#8A6A8E',
        '#5A8A7A', '#8A5A50', '#6A7A8E', '#8A7A4E',
        '#6A6A6A'
    ];

    function getLineColor(key, index) {
        if (LINE_PALETTE[key]) return LINE_PALETTE[key].stroke;
        return FALLBACK_COLORS[index % FALLBACK_COLORS.length];
    }

    function formatNum(v) {
        if (v >= 1000000) return (v / 1000000).toFixed(1).replace('.0', '') + ' млн';
        if (v >= 1000) return (v / 1000).toFixed(0) + ' тыс';
        return String(v);
    }

    function svgEl(name, attrs) {
        var el = document.createElementNS(NS, name);
        for (var key in attrs) {
            if (attrs.hasOwnProperty(key)) el.setAttribute(key, attrs[key]);
        }
        return el;
    }

    // ===================================================================
    //  RENDER: multi-line chart (for fo) or bar chart (region/status)
    // ===================================================================
    function renderChart(metric) {
        if (!svg) return;
        while (svg.firstChild) svg.removeChild(svg.firstChild);

        var type = chartData.type || 'bar';

        if (type === 'line-multi') {
            renderLineChart(metric);
        } else {
            renderBarChart(metric);
        }
    }

    // ===================================================================
    //  BAR CHART (for region / status)
    // ===================================================================
    function renderBarChart(metric) {
        var items = chartData.items;
        if (!items || items.length === 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        svg.removeAttribute('hidden');
        if (emptyEl) emptyEl.setAttribute('hidden', '');

        var cfg = itemConfig(metric);
        var n = items.length;
        var BAR_HEIGHT = 18;
        var BAR_GAP = 4;
        var PADDING = { left: 110, right: 70, top: 14, bottom: 14 };

        var barAreaH = n * (BAR_HEIGHT + BAR_GAP);
        var totalH = Math.max(220, barAreaH + PADDING.top + PADDING.bottom + 10);
        svg.setAttribute('viewBox', '0 0 1200 ' + totalH);

        var chartW = 1200 - PADDING.left - PADDING.right;

        var maxVal = 0;
        for (var i = 0; i < n; i++) {
            var v = items[i][cfg.key];
            if (v > maxVal) maxVal = v;
        }
        if (maxVal <= 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        var magnitude = Math.pow(10, Math.floor(Math.log10(maxVal)));
        maxVal = Math.ceil(maxVal / magnitude) * magnitude;
        if (maxVal <= 0) { svg.setAttribute('hidden', ''); return; }

        for (var i = 0; i < n; i++) {
            var item = items[i];
            var val = item[cfg.key] || 0;
            var barW = (val / maxVal) * chartW;
            var y = PADDING.top + i * (BAR_HEIGHT + BAR_GAP);
            var x = PADDING.left;

            var labelText = svgEl('text', {
                'x': String(PADDING.left - 6),
                'y': String(y + BAR_HEIGHT / 2 + 4),
                'text-anchor': 'end',
                'fill': 'rgba(74, 68, 61, 0.72)',
                'font-size': '10',
                'font-weight': '600',
                'class': 'chart-axis-label'
            });
            labelText.textContent = item.short_label || item.label;
            svg.appendChild(labelText);

            if (barW > 0) {
                var rect = svgEl('rect', {
                    'x': String(x),
                    'y': String(y),
                    'width': String(Math.max(barW, 2)),
                    'height': String(BAR_HEIGHT),
                    'fill': cfg.color,
                    'rx': '1',
                    'ry': '1',
                    'class': 'chart-bar'
                });
                svg.appendChild(rect);

                var valText = svgEl('text', {
                    'x': String(x + barW + 6),
                    'y': String(y + BAR_HEIGHT / 2 + 4),
                    'fill': 'rgba(74, 68, 61, 0.58)',
                    'font-size': '10',
                    'font-weight': '700',
                    'class': 'chart-value-label'
                });
                valText.textContent = cfg.unit ? formatNum(val) + ' ' + cfg.unit : val.toLocaleString('ru-RU');
                svg.appendChild(valText);
            }
        }
    }

    function itemConfig(metric) {
        if (metric === 'weight') {
            return { label: 'Вес', key: 'weight', unit: 'кг', color: '#A1622A', hover: '#7A421C', soft: 'rgba(161, 98, 42, 0.14)' };
        }
        return { label: 'Заявки', key: 'requests', unit: '', color: '#4E6F86', hover: '#35566B', soft: 'rgba(78, 111, 134, 0.14)' };
    }

    // ===================================================================
    //  MULTI-LINE CHART (for fo)
    // ===================================================================
    function renderLineChart(metric) {
        var periods = chartData.periods;
        var series = chartData.series;

        if (!periods || periods.length === 0 || !series || series.length === 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        svg.removeAttribute('hidden');
        if (emptyEl) emptyEl.setAttribute('hidden', '');

        var PADDING = { left: 46, right: 46, top: 18, bottom: 24 };
        var CHART_H = 180;

        svg.setAttribute('viewBox', '0 0 1200 ' + CHART_H);

        var plotW = 800 - PADDING.left - PADDING.right;
        var plotH = CHART_H - PADDING.top - PADDING.bottom;
        var plotX0 = PADDING.left;
        var plotY0 = PADDING.top;
        var plotX1 = plotX0 + plotW;
        var plotY1 = plotY0 + plotH;

        // Compute max value across all series
        var maxVal = 0;
        for (var si = 0; si < series.length; si++) {
            var s = series[si];
            for (var pi = 0; pi < periods.length; pi++) {
                var pk = periods[pi].key;
                var v = (s.values && s.values[pk]) ? (s.values[pk][metric] || 0) : 0;
                if (v > maxVal) maxVal = v;
            }
        }

        if (maxVal <= 0) {
            svg.setAttribute('hidden', '');
            if (emptyEl) emptyEl.removeAttribute('hidden');
            return;
        }

        // Nice max
        var magnitude = Math.pow(10, Math.floor(Math.log10(maxVal)));
        var niceMax = Math.ceil(maxVal / magnitude) * magnitude;
        if (niceMax <= maxVal) niceMax = niceMax + magnitude;
        maxVal = niceMax;

        var nPeriods = periods.length;
        var stepX = nPeriods > 1 ? plotW / (nPeriods - 1) : plotW / 2;

        // ================================================================
        //  Grid lines (horizontal)
        // ================================================================
        var numGridLines = 4;
        var gridStep = maxVal / numGridLines;
        var gridMag = Math.pow(10, Math.floor(Math.log10(gridStep)));
        var gridNice = Math.ceil(gridStep / gridMag) * gridMag;
        if (gridNice <= 0) gridNice = gridStep;

        for (var gi = 0; gi <= numGridLines; gi++) {
            var val = gridNice * gi;
            if (val > maxVal) val = maxVal;
            var y = plotY1 - (val / maxVal) * plotH;
            if (y < plotY0 || y > plotY1) continue;

            var line = svgEl('line', {
                'x1': String(plotX0),
                'y1': String(y),
                'x2': String(plotX1),
                'y2': String(y),
                'stroke': 'rgba(74, 68, 61, 0.10)',
                'stroke-width': '1'
            });
            svg.appendChild(line);

            var label = svgEl('text', {
                'x': String(plotX0 - 4),
                'y': String(y + 4),
                'text-anchor': 'end',
                'fill': 'rgba(74, 68, 61, 0.48)',
                'font-size': '8',
                'class': 'chart-axis-label'
            });
            label.textContent = formatNum(val);
            svg.appendChild(label);
        }

        // ================================================================
        //  X axis labels
        // ================================================================
        var compactLabels = nPeriods > 8;
        var labelStep = 1;
        if (compactLabels) {
            labelStep = Math.ceil(nPeriods / 6);
        }

        // Transform periods to pixel positions
        var periodXPositions = [];
        for (var pi = 0; pi < nPeriods; pi++) {
            periodXPositions.push(plotX0 + pi * stepX);
        }

        for (var pi = 0; pi < nPeriods; pi++) {
            if (compactLabels && pi % labelStep !== 0 && pi !== nPeriods - 1) continue;
            var xPos = periodXPositions[pi];
            var lbl = periods[pi].label;

            // Shorten label: show compact date range
            var m = lbl.match(/^(\d+)\.(\d+)/);
            if (m) {
                lbl = m[1] + '.' + m[2];
            } else if (lbl.length > 8) {
                lbl = lbl.substring(0, 8);
            }

            var label = svgEl('text', {
                'x': String(xPos),
                'y': String(plotY1 + 14),
                'text-anchor': 'end',
                'transform': 'rotate(-20 ' + xPos + ' ' + (plotY1 + 14) + ')',
                'fill': 'rgba(74, 68, 61, 0.45)',
                'font-size': '7.5',
                'class': 'chart-axis-label'
            });
            label.textContent = lbl;
            svg.appendChild(label);
        }

        // ================================================================
        //  Draw series lines + dots
        // ================================================================
        for (var si = 0; si < series.length; si++) {
            var s = series[si];
            var color = getLineColor(s.key, si);
            var points = [];

            for (var pi = 0; pi < nPeriods; pi++) {
                var pk = periods[pi].key;
                var val = (s.values && s.values[pk]) ? (s.values[pk][metric] || 0) : 0;
                var xPos = periodXPositions[pi];
                var yPos = plotY1 - (val / maxVal) * plotH;
                if (yPos < plotY0) yPos = plotY0;
                if (yPos > plotY1) yPos = plotY1;
                points.push({ x: xPos, y: yPos, val: val });
            }

            // Build polyline points string
            var ptsStr = '';
            for (var pi = 0; pi < points.length; pi++) {
                if (pi > 0) ptsStr += ' ';
                ptsStr += points[pi].x.toFixed(1) + ',' + points[pi].y.toFixed(1);
            }

            var polyline = svgEl('polyline', {
                'points': ptsStr,
                'fill': 'none',
                'stroke': color,
                'stroke-width': '1.4',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'class': 'chart-line'
            });
            svg.appendChild(polyline);

            // Dots
            for (var pi = 0; pi < points.length; pi++) {
                var pt = points[pi];
                if (pt.val === 0) continue; // skip zero dots

                var dot = svgEl('circle', {
                    'cx': String(pt.x),
                    'cy': String(pt.y),
                    'r': '2',
                    'fill': '#f5f3ee',
                    'stroke': color,
                    'stroke-width': '1.2',
                    'class': 'chart-dot'
                });
                svg.appendChild(dot);
            }
        }

        // ================================================================
        //  Legend — compact, one row if possible
        // ================================================================
        var legendSvg = svgEl('g', { 'class': 'chart-legend' });
        var xOff = plotX0;
        var legendY = 2;

        for (var si = 0; si < series.length; si++) {
            var s = series[si];
            var color = getLineColor(s.key, si);
            var lbl = s.label || s.key;

            var itemW = lbl.length * 6 + 16;
            if (xOff + itemW > plotX1) {
                xOff = plotX0;
                legendY += 10;
            }

            var legendItem = svgEl('g', { 'transform': 'translate(' + xOff + ', ' + legendY + ')' });

            var rect = svgEl('rect', {
                'x': '0',
                'y': '-3',
                'width': '6',
                'height': '6',
                'rx': '1',
                'fill': color,
                'opacity': '0.85'
            });
            legendItem.appendChild(rect);

            var text = svgEl('text', {
                'x': '9',
                'y': '1.5',
                'fill': 'rgba(74, 68, 61, 0.65)',
                'font-size': '8',
                'font-weight': '600',
                'class': 'chart-legend-label'
            });
            text.textContent = lbl;
            legendItem.appendChild(text);

            legendSvg.appendChild(legendItem);
            xOff += itemW + 4;
        }

        svg.appendChild(legendSvg);
    }

    // ===================================================================
    //  Metric switcher
    // ===================================================================
    function setActiveMetric(metric) {
        currentMetric = metric;
        switchBtns.forEach(function (btn) {
            var isActive = btn.getAttribute('data-chart-metric') === metric;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderChart(metric);
        if (chartMetricInput) chartMetricInput.value = metric;
    }

    switchBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveMetric(this.getAttribute('data-chart-metric'));
        });
    });

    setActiveMetric(currentMetric);
})();
</script>
