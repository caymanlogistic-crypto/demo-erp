<?php
/**
 * Контент страницы «Отчётность».
 *
 * Переменные (из ReportsController):
 *   $reportData      — результат buildDeliveredByFO / buildDeliveredByRegions / buildStatusSummary
 *      + report_title, active_report, service (ReportsService)
 *
 * @var array $reportData
 */

use App\Modules\Reports\Services\ReportsService;

/** @var string $activeReport */
$activeReport = $reportData['active_report'] ?? 'delivered_fo';
/** @var string $reportTitle */
$reportTitle = $reportData['report_title'] ?? 'Сдано по федеральным округам';
/** @var ReportsService $service */
$service = $reportData['service'] ?? null;
/** @var array $rows */
$rows = $reportData['rows'] ?? [];
/** @var array $totals */
$totals = $reportData['totals'] ?? [];
/** @var int $unmatched */
$unmatched = $reportData['unmatched'] ?? 0;
?>
<!-- reports page: active=<?= htmlspecialchars($activeReport, ENT_QUOTES, 'UTF-8') ?> unmatched=<?= (int) $unmatched ?> -->

<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-title">Отчётность</div>
        <div class="page-summary"><span>Сводные отчёты по заявкам, статусам, регионам и федеральным округам</span></div>
    </div>
</div>

<!-- Reports tabs -->
<div class="reports-tabs" role="tablist" aria-label="Отчёты">
    <a class="reports-tab<?= $activeReport === 'delivered_fo' ? ' active' : '' ?>"
       role="tab"
       aria-selected="<?= $activeReport === 'delivered_fo' ? 'true' : 'false' ?>"
       href="?module=reports&report=delivered_fo">Сдано по ФО</a>
    <a class="reports-tab<?= $activeReport === 'delivered_regions' ? ' active' : '' ?>"
       role="tab"
       aria-selected="<?= $activeReport === 'delivered_regions' ? 'true' : 'false' ?>"
       href="?module=reports&report=delivered_regions">Сдано по регионам</a>
    <a class="reports-tab<?= $activeReport === 'status_summary' ? ' active' : '' ?>"
       role="tab"
       aria-selected="<?= $activeReport === 'status_summary' ? 'true' : 'false' ?>"
       href="?module=reports&report=status_summary">По статусам</a>
</div>

<!-- Table card -->
<div class="table-card report-table-card" style="flex: 1; min-height: 0;">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= count($rows) ?></b>
                <span class="summary-main-unit">строк</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Итого заявок</span>
                <b><?= number_format((int) ($totals['total_count'] ?? 0), 0, '.', ' ') ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Итого масса</span>
                <b><?= number_format((int) round($totals['total_weight'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
        </div>
    </div>

    <div class="table-scroll">
        <?php if (!empty($rows)): ?>
            <?php if ($activeReport === 'status_summary'): ?>
                <?= renderStatusSummaryTable($rows, $totals, $reportData, $service) ?>
            <?php else: ?>
                <?= renderMonthTable($rows, $totals, $reportData, $service, $activeReport) ?>
            <?php endif; ?>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
            Нет данных для отображения
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// ================================================================
//  Inline helper functions (template-only)
// ================================================================

/**
 * Render month-based table (delivered_fo, delivered_regions).
 */
function renderMonthTable(array $rows, array $totals, array $data, ?ReportsService $service, string $activeReport): string
{
    $months = $data['months'] ?? [];
    $monthKeys = array_keys($months);
    // Build month labels
    $monthLabels = [];
    $allMonthKeys = [];
    foreach ($months as $mk) {
        $allMonthKeys[] = $mk;
        $monthLabels[$mk] = $service ? $service->formatMonthLabel($allMonthKeys, $mk) : $mk;
    }

    $isRegionReport = ($activeReport === 'delivered_regions');

    ob_start();
    ?>
    <table class="table reports-table">
        <colgroup>
            <col style="width: <?= $isRegionReport ? '160px' : '100px' ?>;">
            <?php foreach ($months as $mk): ?>
            <col style="width: 70px;">
            <col style="width: 60px;">
            <?php endforeach; ?>
            <col style="width: 80px;">
            <col style="width: 70px;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2"><?= $isRegionReport ? 'РЕГИОН' : 'ФО' ?></th>
                <?php foreach ($months as $mk): ?>
                <th colspan="2" style="text-align: center;"><?= htmlspecialchars($monthLabels[$mk] ?? $mk, ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
                <th colspan="2" style="text-align: center;">ИТОГО</th>
            </tr>
            <tr>
                <?php foreach ($months as $mk): ?>
                <th style="text-align: right;">ВЕС</th>
                <th style="text-align: right;">ЗАЯВОК</th>
                <?php endforeach; ?>
                <th style="text-align: right;">ВЕС</th>
                <th style="text-align: right;">ЗАЯВОК</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php if ($isRegionReport): ?>
                <td class="report-cell-region" title="<?= htmlspecialchars($row['region_display'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="report-region-name"><?= htmlspecialchars($row['region_display'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($row['district_short'])): ?>
                    <span class="report-muted"><?= htmlspecialchars($row['district_short'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </td>
                <?php else: ?>
                <td class="report-cell-district" title="<?= htmlspecialchars($row['district_full'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($row['district_title'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                </td>
                <?php endif; ?>
                <?php foreach ($months as $mk): ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($row['cells'][$mk]['weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($row['cells'][$mk]['count'] ?? 0)) ?></td>
                <?php endforeach; ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($row['total_weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($row['total_count'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($totals)): ?>
        <tfoot>
            <tr class="report-total-row">
                <td><?= htmlspecialchars($totals['district_title'] ?? $totals['region_display'] ?? 'Общий итог', ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($months as $mk): ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($totals['cells'][$mk]['weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($totals['cells'][$mk]['count'] ?? 0)) ?></td>
                <?php endforeach; ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($totals['total_weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($totals['total_count'] ?? 0)) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    <?php
    return ob_get_clean();
}

/**
 * Render status summary table.
 */
function renderStatusSummaryTable(array $rows, array $totals, array $data, ?ReportsService $service): string
{
    $statuses = $data['statuses'] ?? [];
    ob_start();
    ?>
    <table class="table reports-table">
        <colgroup>
            <col style="width: 100px;">
            <?php foreach ($statuses as $sk): ?>
            <col style="width: 80px;">
            <col style="width: 70px;">
            <?php endforeach; ?>
            <col style="width: 80px;">
            <col style="width: 70px;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">ФО</th>
                <?php foreach ($statuses as $sk): ?>
                <th colspan="2" style="text-align: center;" title="<?= htmlspecialchars($service ? $service->statusTitle($sk) : $sk, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($service ? $service->statusLabel($sk) : $sk, ENT_QUOTES, 'UTF-8') ?>
                </th>
                <?php endforeach; ?>
                <th colspan="2" style="text-align: center;">ИТОГО</th>
            </tr>
            <tr>
                <?php foreach ($statuses as $sk): ?>
                <th style="text-align: right;">ВЕС</th>
                <th style="text-align: right;">ЗАЯВОК</th>
                <?php endforeach; ?>
                <th style="text-align: right;">ВЕС</th>
                <th style="text-align: right;">ЗАЯВОК</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="report-cell-district" title="<?= htmlspecialchars($row['district_full'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($row['district_title'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                </td>
                <?php foreach ($statuses as $sk): ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($row['cells'][$sk]['weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($row['cells'][$sk]['count'] ?? 0)) ?></td>
                <?php endforeach; ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($row['total_weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($row['total_count'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($totals)): ?>
        <tfoot>
            <tr class="report-total-row">
                <td><?= htmlspecialchars($totals['district_title'] ?? 'Общий итог', ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($statuses as $sk): ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($totals['cells'][$sk]['weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($totals['cells'][$sk]['count'] ?? 0)) ?></td>
                <?php endforeach; ?>
                <td class="report-cell-weight"><?= ReportsService::formatWeight((float) ($totals['total_weight'] ?? 0)) ?></td>
                <td class="cell-num"><?= ReportsService::formatCount((int) ($totals['total_count'] ?? 0)) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    <?php
    return ob_get_clean();
}
?>
