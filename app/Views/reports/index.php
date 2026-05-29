<?php
/**
 * Контент страницы «Отчётность» — перестроен по эталону Statistics.
 *
 * Переменные:
 *   $period       — week|month|custom
 *   $dateType     — delivery|pickup
 *   $dimension    — fo|region|status
 *   $chartMetric  — requests|weight
 *   $dateFrom     — YYYY-MM-DD
 *   $dateTo       — YYYY-MM-DD
 *   $warning      — string|null
 *   $data         — array (результат buildReports)
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
/** @var array $data */
/** @var array $summary */
/** @var array $rows */
/** @var int $unmatched */
/** @var array $chartData */
/** @var ReportsService $service */

$totalRows = $summary['rows_total'] ?? count($rows);

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

<!-- Filter bar -->
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
<div class="table-card reports-card" style="flex: 1; min-height: 0;">
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
                <!-- reports chart: enabled type=<?= htmlspecialchars($chartData['type'] ?? 'bar', ENT_QUOTES, 'UTF-8') ?> metric=<?= htmlspecialchars($chartMetric, ENT_QUOTES, 'UTF-8') ?> periods=<?= count($chartData['periods'] ?? []) ?> districts=<?= count($chartData['series'] ?? []) ?> -->
                <svg class="reports-chart-svg" viewBox="0 0 1200 210" preserveAspectRatio="none" role="img" aria-label="График отчётности"<?= $chartData['enabled'] ? '' : ' hidden' ?>></svg>
                <div class="statistics-chart-tooltip" hidden></div>
                <div class="statistics-chart-empty"<?= $chartData['enabled'] ? ' hidden' : '' ?>>
                    <?= !empty($rows) ? 'Нет данных для графика.' : 'Нет данных для отображения.' ?>
                </div>
            </div>
        </div>
        <script type="application/json" id="reportsChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    </section>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>

        <?php if ($dimension === 'fo'): ?>
        <!-- reports districts: unmatched=<?= (int) $unmatched ?> -->
        <?php
        $districtTitles = $data['district_titles'] ?? [];
        $hasUnmatched = $data['has_unmatched'] ?? false;
        $numDistricts = count($districtTitles);
        $totals = $data['totals'] ?? [];
        ?>
        <table class="table reports-matrix-table">
            <colgroup>
                <col class="col-matrix-period">
                <?php for ($i = 0; $i < $numDistricts; $i++): ?><col class="col-matrix-district"><?php endfor; ?>
                <?php for ($i = 0; $i < $numDistricts; $i++): ?><col class="col-matrix-district"><?php endfor; ?>
            </colgroup>
            <thead>
                <tr class="reports-matrix-group-head">
                    <th class="th-period" rowspan="2">Период</th>
                    <th class="th-group" colspan="<?= $numDistricts ?>">Заявки</th>
                    <th class="th-group" colspan="<?= $numDistricts ?>">Вес</th>
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
                    ?><td class="td-cell-num"><?= $val > 0 ? number_format($val, 0, '.', ' ') : '—' ?></td><?php endforeach; ?>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $row['weight_by_district'][$dk] ?? 0;
                    ?><td class="td-cell-weight"><?= $val > 0 ? number_format($val, 0, '.', ' ') . ' кг' : '—' ?></td><?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($totals)): ?>
            <tfoot>
                <tr class="reports-total-row">
                    <td class="td-period">Итого</td>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $totals['requests_by_district'][$dk] ?? 0;
                    ?><td class="td-cell-num"><?= $val > 0 ? number_format($val, 0, '.', ' ') : '—' ?></td><?php endforeach; ?>
                    <?php foreach ($districtTitles as $dk => $dt):
                        $val = $totals['weight_by_district'][$dk] ?? 0;
                    ?><td class="td-cell-weight"><?= $val > 0 ? number_format($val, 0, '.', ' ') . ' кг' : '—' ?></td><?php endforeach; ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <?php elseif ($dimension === 'region'): ?>
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
                $totalWeightSum = 0.0; $totalRequestsSum = 0;
                foreach ($rows as $r) { $totalWeightSum += $r['weight_kg'] ?? 0; $totalRequestsSum += $r['requests'] ?? 0; }
                foreach ($rows as $row):
                    $rWeight = $row['weight_kg'] ?? 0; $rRequests = $row['requests'] ?? 0; $rAvg = $row['avg_request_kg'] ?? 0;
                    $shareR = $totalRequestsSum > 0 ? round(($rRequests / $totalRequestsSum) * 100, 1) : 0;
                    $shareW = $totalWeightSum > 0 ? round(($rWeight / $totalWeightSum) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($service->formatPeriodLabel($period === 'month' ? date('Y-m') : ($period === 'week' ? date('o-\WW') : 'all'), $period, $dateFrom, $dateTo), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="report-cell-region"><?= htmlspecialchars($row['region'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
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
                $totalWeightSum = 0.0; $totalRequestsSum = 0;
                foreach ($rows as $r) { $totalWeightSum += $r['weight_kg'] ?? 0; $totalRequestsSum += $r['requests'] ?? 0; }
                foreach ($rows as $row):
                    $rWeight = $row['weight_kg'] ?? 0; $rRequests = $row['requests'] ?? 0; $rAvg = $row['avg_request_kg'] ?? 0;
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
            if (input) { input.disabled = !visible; }
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

    if (periodSelect) { periodSelect.addEventListener('change', function () { updateCustomVisibility(); if (!isCustom()) submitAuto(); }); }
    if (dateTypeSelect) { dateTypeSelect.addEventListener('change', function () { if (!isCustom()) submitAuto(); }); }
    if (dimensionSelect) { dimensionSelect.addEventListener('change', function () { if (!isCustom()) submitAuto(); }); }
    updateCustomVisibility();
})();
</script>

<script>
(function () {
    var CHART_SVG_VIEWBOX = { w: 1200, h: 210 };
    var PADDING = { left: 48, right: 24, top: 18, bottom: 34 };
    var GRID_LINES = 4;
    var LINE_WIDTH = 2.5;
    var DOT_RADIUS = 3.5;
    var DOT_HOVER_RADIUS = 5;
    var SMOOTH_TENSION = 0.18;

    var NS = 'http://www.w3.org/2000/svg';

    var chartPanel = document.querySelector('[data-reports-chart]');
    var jsonEl = document.getElementById('reportsChartData');
    var chartData = null;
    if (jsonEl) { try { chartData = JSON.parse(jsonEl.textContent); } catch (e) { chartData = null; } }
    if (!chartPanel || !chartData || !chartData.enabled) return;

    var svg = chartPanel.querySelector('.reports-chart-svg');
    var tooltip = chartPanel.querySelector('.statistics-chart-tooltip');
    var emptyEl = chartPanel.querySelector('.statistics-chart-empty');
    var switchBtns = chartPanel.querySelectorAll('.chart-metric-btn');
    var chartMetricInput = document.getElementById('chartMetricInput');
    var currentMetric = chartMetricInput ? chartMetricInput.value : 'requests';
    var guideLine = null;

    var metricConfigs = {
        weight:   { label: 'Вес', key: 'weight', unit: 'кг', unitLabel: 'Вес, т', color: '#A1622A', hover: '#7A421C', soft: 'rgba(161, 98, 42, 0.055)' },
        requests: { label: 'Заявки', key: 'requests', unit: '', unitLabel: 'Заявки, шт.', color: '#4E6F86', hover: '#35566B', soft: 'rgba(78, 111, 134, 0.055)' }
    };
    var LINE_PALETTE = {
        cfo:  '#4A6A7A', szfo: '#8B6F5E', yfo: '#7A8A5A', skfo: '#8A6A8E',
        pfo:  '#5A8A7A', urfo: '#8A5A50', sfo: '#6A7A8E', dfo: '#8A7A4E'
    };
    var FALLBACK_COLORS = ['#4A6A7A','#8B6F5E','#7A8A5A','#8A6A8E','#5A8A7A','#8A5A50','#6A7A8E','#8A7A4E','#6A6A6A'];

    function getLineColor(key, index) { return LINE_PALETTE[key] || FALLBACK_COLORS[index % FALLBACK_COLORS.length]; }

    function formatNumber(v) {
        if (v >= 1000000) return (v / 1000000).toFixed(1).replace('.0','') + ' млн';
        if (v >= 1000) return (v / 1000).toFixed(0) + ' тыс';
        return String(v);
    }

    function svgEl(name, attrs) {
        var el = document.createElementNS(NS, name);
        for (var key in attrs) { if (attrs.hasOwnProperty(key)) el.setAttribute(key, attrs[key]); }
        return el;
    }

    function buildSmoothPath(points, tension) {
        if (points.length < 2) return '';
        var n = points.length, d = '';
        for (var i = 0; i < n - 1; i++) {
            var p0 = i > 0 ? points[i-1] : points[i], p1 = points[i], p2 = points[i+1], p3 = i < n-2 ? points[i+2] : p2;
            var cp1x = p1.x + (p2.x - p0.x) * tension, cp1y = p1.y + (p2.y - p0.y) * tension;
            var cp2x = p2.x - (p3.x - p1.x) * tension, cp2y = p2.y - (p3.y - p1.y) * tension;
            if (i === 0) d += 'M' + p1.x.toFixed(1) + ',' + p1.y.toFixed(1);
            d += ' C' + cp1x.toFixed(1) + ',' + cp1y.toFixed(1) + ' ' + cp2x.toFixed(1) + ',' + cp2y.toFixed(1) + ' ' + p2.x.toFixed(1) + ',' + p2.y.toFixed(1);
        }
        return d;
    }

    function renderChart(metric) {
        if (!svg) return;
        while (svg.firstChild) svg.removeChild(svg.firstChild);

        var type = chartData.type || 'bar';
        if (type === 'grouped-bars') { renderGroupedBars(metric); } else { renderBarChart(metric); }
    }

    // =============================================================
    //  BAR CHART (for region / status)
    // =============================================================
    function renderBarChart(metric) {
        var items = chartData.items;
        if (!items || items.length === 0) { svg.setAttribute('hidden',''); if (emptyEl) emptyEl.removeAttribute('hidden'); return; }
        svg.removeAttribute('hidden'); if (emptyEl) emptyEl.setAttribute('hidden','');

        var cfg = metricConfigs[metric];
        var n = items.length;
        var BAR_H = 18, BAR_G = 4;
        var PAD = { left: 110, right: 70, top: 14, bottom: 14 };
        var barAreaH = n * (BAR_H + BAR_G);
        var totalH = Math.max(210, barAreaH + PAD.top + PAD.bottom + 10);
        svg.setAttribute('viewBox','0 0 1200 ' + totalH);
        var chartW = 1200 - PAD.left - PAD.right;

        var maxV = 0; for (var i = 0; i < n; i++) { var v = items[i][cfg.key]; if (v > maxV) maxV = v; }
        if (maxV <= 0) { svg.setAttribute('hidden',''); if (emptyEl) emptyEl.removeAttribute('hidden'); return; }
        var mag = Math.pow(10, Math.floor(Math.log10(maxV))); maxV = Math.ceil(maxV / mag) * mag;
        if (maxV <= 0) { svg.setAttribute('hidden',''); return; }

        for (var i = 0; i < n; i++) {
            var item = items[i], val = item[cfg.key] || 0;
            var barW = (val / maxV) * chartW;
            var y = PAD.top + i * (BAR_H + BAR_G), x = PAD.left;
            var lbl = svgEl('text',{'x':String(PAD.left-6),'y':String(y+BAR_H/2+4),'text-anchor':'end','fill':'rgba(74,68,61,0.72)','font-size':'10','font-weight':'600'}); lbl.textContent = item.short_label || item.label; svg.appendChild(lbl);
            if (barW > 0) {
                var rect = svgEl('rect',{'x':String(x),'y':String(y),'width':String(Math.max(barW,2)),'height':String(BAR_H),'fill':cfg.color,'rx':'1','ry':'1'}); svg.appendChild(rect);
                var valT = svgEl('text',{'x':String(x+barW+6),'y':String(y+BAR_H/2+4),'fill':'rgba(74,68,61,0.58)','font-size':'10','font-weight':'700'}); valT.textContent = cfg.unit ? formatNumber(val) + ' ' + cfg.unit : val.toLocaleString('ru-RU'); svg.appendChild(valT);
            }
        }
    }

    // =============================================================
    //  GROUPED BAR CHART (for dimension=fo)
    // =============================================================
    var DISTRICT_COLORS = {
        cfo:  '#5A7A8A', szfo: '#6A8A8A', yfo: '#A08060', skfo: '#8A6A8A',
        pfo:  '#A09060', urfo: '#7A8A6A', sfo: '#8A7070', dfo: '#7A7A7A',
        _unmatched: '#555555'
    };
    var FALLBACK_BAR_COLORS = ['#5A7A8A','#6A8A8A','#A08060','#8A6A8A','#A09060','#7A8A6A','#8A7070','#7A7A7A','#555'];

    function getDistrictColor(key, index) {
        return DISTRICT_COLORS[key] || FALLBACK_BAR_COLORS[index % FALLBACK_BAR_COLORS.length];
    }

    function renderGroupedBars(metric) {
        var periods = chartData.periods, series = chartData.series;
        if (!periods || !series || periods.length === 0 || series.length === 0) {
            svg.setAttribute('hidden',''); if (emptyEl) emptyEl.removeAttribute('hidden'); return;
        }
        svg.removeAttribute('hidden'); if (emptyEl) emptyEl.setAttribute('hidden','');

        svg.setAttribute('viewBox','0 0 ' + CHART_SVG_VIEWBOX.w + ' ' + CHART_SVG_VIEWBOX.h);

        // plot area
        var pw = CHART_SVG_VIEWBOX.w - PADDING.left - PADDING.right;
        var ph = CHART_SVG_VIEWBOX.h - PADDING.top - PADDING.bottom;
        var pX0 = PADDING.left, pY0 = PADDING.top;
        var pY1 = pY0 + ph;

        var nP = periods.length, nS = series.length;

        // compute max value from visible series
        var maxV = 0;
        for (var si = 0; si < nS; si++) {
            var sv = series[si].values;
            if (!sv) continue;
            for (var pi = 0; pi < nP; pi++) {
                var v = (sv[periods[pi].key] ? sv[periods[pi].key][metric] : 0) || 0;
                if (v > maxV) maxV = v;
            }
        }
        if (maxV <= 0) { svg.setAttribute('hidden',''); if (emptyEl) emptyEl.removeAttribute('hidden'); return; }
        var mag = Math.pow(10, Math.floor(Math.log10(maxV)));
        maxV = Math.ceil(maxV / mag) * mag;
        if (maxV <= 0) { svg.setAttribute('hidden',''); return; }

        // Y grid
        var gridStep = Math.ceil(maxV / GRID_LINES);
        for (var gi = 0; gi <= GRID_LINES; gi++) {
            var gv = gridStep * gi, gy = pY1 - (gv / maxV) * ph;
            var gl = svgEl('line',{'x1':String(pX0),'y1':String(Math.round(gy)),'x2':String(pX0+pw),'y2':String(Math.round(gy)),'stroke':'rgba(76,70,62,0.12)','stroke-width':'1','stroke-linecap':'round'});
            svg.appendChild(gl);
            var yl = svgEl('text',{'x':String(pX0-6),'y':String(Math.round(gy)+3),'text-anchor':'end','fill':'rgba(74,68,61,0.58)','font-size':'9','font-weight':'700'});
            if (metric === 'weight') {
                yl.textContent = gv >= 1000 ? String(Math.round(gv/1000)) + 'k' : String(Math.round(gv));
            } else {
                yl.textContent = formatNumber(gv);
            }
            svg.appendChild(yl);
        }

        // Bar geometry
        var groupWidth = pw / nP;
        var groupPadding = groupWidth * 0.28;
        var barsArea = groupWidth - groupPadding;
        var barGap = Math.max(1, barsArea * 0.06);
        var totalGaps = Math.max(1, nS - 1) * barGap;
        var barWidth = Math.max(2, (barsArea - totalGaps) / nS);
        // clamp if too thin
        if (barWidth * nS + totalGaps > barsArea) {
            barWidth = Math.max(2, (barsArea - totalGaps) / nS);
        }

        // Bars per period group
        var periodGroups = [];
        for (var pi = 0; pi < nP; pi++) {
            var gx = pX0 + pi * groupWidth + groupPadding / 2;
            var bars = [];
            for (var si = 0; si < nS; si++) {
                var s = series[si], pk = periods[pi].key;
                var val = (s.values && s.values[pk] ? s.values[pk][metric] : 0) || 0;
                var barH = (val / maxV) * ph;
                var bx = gx + si * (barWidth + barGap);
                var by = pY1 - barH;
                var color = getDistrictColor(s.key, si);
                bars.push({ x: Math.round(bx), y: Math.round(Math.max(by, pY0)), w: Math.round(barWidth), h: Math.round(Math.max(barH, 0.5)), val: val, color: color, key: s.key, label: s.label, title: s.title });
            }
            periodGroups.push({ x: gx, w: barsArea, bars: bars, period: periods[pi] });
        }

        // Render bars
        for (var pi = 0; pi < nP; pi++) {
            var grp = periodGroups[pi];
            for (var bi = 0; bi < grp.bars.length; bi++) {
                var bar = grp.bars[bi];
                if (bar.h > 0.3) {
                    var rect = svgEl('rect',{'x':String(bar.x),'y':String(bar.y),'width':String(bar.w),'height':String(bar.h),'fill':bar.color,'rx':'1','ry':'1','opacity':'0.88','data-period-index':String(pi),'data-series-index':String(bi)});
                    svg.appendChild(rect);
                }
            }
        }

        // X labels (short)
        var labelEvery = 1;
        if (nP > 10) labelEvery = 2;
        if (nP > 18) labelEvery = 3;
        for (var pi = 0; pi < nP; pi++) {
            if (pi % labelEvery !== 0 && pi !== nP - 1) continue;
            var cx = pX0 + pi * groupWidth + groupWidth / 2;
            var xlbl = periods[pi].shortLabel || periods[pi].label.substring(0,5);
            var xAnchor = nP > 10 ? 'end' : 'middle';
            var xRot = nP > 12 ? 'rotate(-22 '+cx+' '+(pY1+16)+')' : '';
            var xl = svgEl('text',{'x':String(cx),'y':String(pY1+16),'text-anchor':periods.length>10?'end':'middle','transform':nP>12?'rotate(-22 '+cx+' '+(pY1+16)+')':'','fill':'rgba(74,68,61,0.45)','font-size':nP>12?'7':'8'});
            xl.textContent = xlbl;
            svg.appendChild(xl);
        }

        // Legend — compact, above plot area
        var lx = pX0, ly = 1;
        var legendG = svgEl('g',{});
        var maxLegendX = pX0 + pw;
        for (var si = 0; si < nS; si++) {
            var s = series[si], color = getDistrictColor(s.key, si), lbl = s.label || s.key;
            var itemW = lbl.length * 5.5 + 14;
            if (lx + itemW > maxLegendX) { lx = pX0; ly += 10; }
            var li = svgEl('g',{'transform':'translate('+lx+','+ly+')'});
            li.appendChild(svgEl('rect',{'x':'0','y':'-3','width':'6','height':'6','rx':'1','fill':color,'opacity':'0.85'}));
            var tx = svgEl('text',{'x':'8','y':'1.5','fill':'rgba(74,68,61,0.62)','font-size':'7.5','font-weight':'600'});
            tx.textContent = lbl; li.appendChild(tx);
            legendG.appendChild(li); lx += itemW + 4;
        }
        svg.appendChild(legendG);

        // Tooltip interaction — mousemove over plot area
        svg._tooltipData = { periodGroups: periodGroups, metric: metric };
        svg.addEventListener('mousemove', function (e) {
            var pt = svg.createSVGPoint();
            var rect = svg.getBoundingClientRect();
            pt.x = e.clientX - rect.left;
            pt.y = e.clientY - rect.top;
            var svgPt = pt.matrixTransform(svg.getScreenCTM().inverse());

            var tData = svg._tooltipData;
            if (!tData || !tooltip) return;

            // find period group by X
            var found = null;
            for (var pi = 0; pi < tData.periodGroups.length; pi++) {
                var g = tData.periodGroups[pi];
                if (svgPt.x >= g.x && svgPt.x <= g.x + g.w) { found = g; break; }
            }
            if (!found) { tooltip.hidden = true; return; }

            var m = tData.metric;
            var lines = [];
            for (var bi = 0; bi < found.bars.length; bi++) {
                var b = found.bars[bi];
                var vStr = m === 'weight' ? String(b.val).replace(/(\d)(?=(\d{3})+$)/g,'$1 ') + ' кг' : String(b.val);
                lines.push({ label: b.label, color: b.color, val: vStr });
            }
            var title = (found.period && found.period.label) ? found.period.label : '';

            var html = '<div style="font-weight:700;font-size:11px;margin-bottom:4px;color:var(--text-main)">' + title + '</div>';
            for (var li = 0; li < lines.length; li++) {
                html += '<div style="display:flex;align-items:center;gap:5px;font-size:10px;margin-bottom:2px;color:var(--text-secondary)">';
                html += '<span style="display:inline-block;width:6px;height:6px;border-radius:1px;background:' + lines[li].color + ';flex-shrink:0"></span>';
                html += '<span style="flex:1">' + lines[li].label + '</span>';
                html += '<b style="color:var(--text-main);text-align:right">' + lines[li].val + '</b>';
                html += '</div>';
            }
            tooltip.innerHTML = html;
            tooltip.hidden = false;

            var tipRect = tooltip.getBoundingClientRect();
            var chartRect = chartPanel.getBoundingClientRect();
            var left = (e.clientX - chartRect.left) + 14;
            var top = (e.clientY - chartRect.top) - 8;
            if (left + tipRect.width > chartRect.width) left = (e.clientX - chartRect.left) - tipRect.width - 14;
            if (top < 0) top = 4;
            if (top + tipRect.height > chartRect.height) top = chartRect.height - tipRect.height - 4;
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        });
        svg.addEventListener('mouseleave', function () { if (tooltip) tooltip.hidden = true; });
    }

    function setActiveMetric(metric) {
        currentMetric = metric;
        switchBtns.forEach(function (btn) {
            var isActive = btn.getAttribute('data-chart-metric') === metric;
            btn.classList.toggle('active', isActive); btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        renderChart(metric);
        if (chartMetricInput) chartMetricInput.value = metric;
    }
    switchBtns.forEach(function (btn) { btn.addEventListener('click', function () { setActiveMetric(this.getAttribute('data-chart-metric')); }); });
    setActiveMetric(currentMetric);
})();
</script>
