<?php
/**
 * Контент страницы «Таймлайн рейсов» (TransportERP shell).
 * Вставляется в app/Views/layouts/main.php.
 *
 * Переменные, переданные из FlightsController:
 *   $tab        — активный таб (planned|in_transit|unloaded)
 *   $tabCounts  — [planned, in_transit, unloaded]
 *   $flights    — обогащённые рейсы
 *   $statusMap  — [статус => [статус, наименование, style, ...]]
 *   $service    — FlightsTimelineService
 *   $summary    — [requests_total: int, weight_total_kg: float]
 */

use App\Modules\Flights\Services\FlightsTimelineService;

/** @var string $tab */
/** @var array $tabCounts */
/** @var array $flights */
/** @var array $statusMap */
/** @var FlightsTimelineService $service */
/** @var array $summary */

$allTabs = [
    'planned'    => 'Планы на вывоз',
    'in_transit' => 'Рейсы в пути',
    'unloaded'   => 'Выгруженные рейсы',
];
?>
<!-- Page header -->
<div class="page-head">
    <div class="page-head-left">
        <div class="page-eyebrow">Рейсы / Таймлайн рейсов</div>
        <div class="page-title">Таймлайн рейсов</div>
        <div class="page-summary"><span>Операционная лента рейсов · read-only</span></div>
    </div>
</div>

<!-- Tabs -->
<div class="flights-tabs">
    <?php foreach ($allTabs as $tabKey => $tabLabel): ?>
        <a class="flights-tab<?= ($tabKey === $tab) ? ' active' : '' ?>"
           href="?module=flights&tab=<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8') ?>
            <span class="flights-tab-count"><?= (int) ($tabCounts[$tabKey] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($flights)): ?>
<!-- Table card -->
<div class="table-card flights-table-card">
    <div class="table-toolbar">
        <div class="summary-left">
            <span class="summary-main">
                <span class="summary-main-dot"></span>
                <span class="summary-main-label">Найдено:</span>
                <b><?= (int) ($tabCounts[$tab] ?? count($flights)) ?></b>
                <span class="summary-main-unit">рейсов</span>
            </span>
        </div>
        <div class="summary-right">
            <span class="summary-chip summary-chip-requests">
                <span class="summary-chip-dot"></span>
                <span>Заявок</span>
                <b id="countRequests"><?= (int) ($summary['requests_total'] ?? 0) ?></b>
            </span>
            <span class="summary-chip summary-chip-weight">
                <span class="summary-chip-dot"></span>
                <span>Масса</span>
                <b id="countWeightKg"><?= number_format((int) ($summary['weight_total_kg'] ?? 0), 0, '.', ' ') ?> кг</b>
            </span>
        </div>
    </div>
    <div class="table-scroll">
        <table class="table flights-table">
            <colgroup>
                <col class="col-flights-toggle">
                <col class="col-flights-date">
                <col class="col-flights-id">
                <col class="col-flights-desc">
                <col class="col-flights-sklad">
                <col class="col-flights-status">
                <col class="col-flights-driver">
                <col class="col-flights-manager">
                <col class="col-flights-prozvon">
                <col class="col-flights-addr">
                <col class="col-flights-weight">
                <col class="col-flights-actions">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>ПЛАН / ФАКТ ДАТА</th>
                    <th>ID</th>
                    <th>ОПИСАНИЕ РЕЙСА</th>
                    <th>СКЛАД</th>
                    <th>СТАТУС</th>
                    <th>ВОДИТЕЛЬ / МАШИНА</th>
                    <th>МЕНЕДЖЕР</th>
                    <th>ПРОЗВОН</th>
                    <th>АДРЕСА / ЗАЯВКИ</th>
                    <th>ВЕС, КГ</th>
                    <th>ДЕЙСТВИЯ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flights as $flight):
                    $flightId = (int) $flight['id'];
                    $dateStr = $service->formatFlightDate($flight, $tab);
                    $dateMarkerClass = $service->getDateMarkerClass($flight, $tab);
                    $statusInfo = $service->getFlightStatus($flight, $statusMap);
                    $description = $flight['comment'] ?? '';
                    $unloadType = $flight['unload_type'] ?? 'OO';
                    $driverName = $flight['driver_name'] ?? '';
                    $vehiclePlate = $flight['vehicle_make_plate'] ?? '';
                    $managerName = $flight['manager_name'] ?? '';
                    $prepFilled = (int) ($flight['prep_time_filled_count'] ?? 0);
                    $totalZayavki = (int) ($flight['total_zayavki_count'] ?? 0);
                    $uniqueAddr = (int) ($flight['unique_addresses_count'] ?? 0);
                    $totalMass = $flight['total_mass_kg'] ?? 0;

                    // Прозвон badge
                    if ($totalZayavki > 0 && $prepFilled === $totalZayavki) {
                        $prozvonClass = 'prozvon-badge complete';
                    } else {
                        $prozvonClass = 'prozvon-badge incomplete';
                    }

                    // Зявки для кнопки "список заявок"
                    $zayavkiList = [];
                    foreach ($flight['zayavki'] as $z) {
                        $zayavkiList[] = [
                            'id'     => (string) ($z['zayavka_id'] ?? ''),
                            'weight' => number_format(floatval($z['mass_netto'] ?? 0), 3, ',', ''),
                        ];
                    }
                    $zayavkiJson = htmlspecialchars(json_encode($zayavkiList, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                ?>
                <!-- Main row -->
                <tr class="flight-row" data-flight-id="<?= $flightId ?>" tabindex="0">
                    <td class="flight-toggle">
                        <span class="flight-toggle-arrow">▶</span>
                    </td>
                    <td class="flight-date <?= htmlspecialchars($dateMarkerClass, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="flight-id">
                        <span class="ref-chip">#<?= $flightId ?></span>
                    </td>
                    <td class="flight-desc" title="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
                        <?= $description !== '' ? htmlspecialchars($description, ENT_QUOTES, 'UTF-8') : '<span class="empty-cell">—</span>' ?>
                    </td>
                    <td class="flight-warehouse">
                        <?= ($unloadType === 'OO' || $unloadType === '') ? '<span class="empty-cell">—</span>' : '<span class="warehouse-badge">СКЛАД</span>' ?>
                    </td>
                    <td class="flight-status-cell">
                        <span class="flight-status <?= htmlspecialchars($statusInfo['css_class'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="flight-status-dot"></span>
                            <?= htmlspecialchars($statusInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="flight-driver" title="<?= htmlspecialchars(trim(($driverName ?: '') . ' ' . ($vehiclePlate ?: '')), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($driverName)): ?>
                            <?= htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($vehiclePlate)): ?>
                                <br><span class="vehicle-plate"><?= htmlspecialchars($vehiclePlate, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="empty-cell">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="flight-manager" title="<?= htmlspecialchars($managerName, ENT_QUOTES, 'UTF-8') ?>">
                        <?= !empty($managerName) ? htmlspecialchars($managerName, ENT_QUOTES, 'UTF-8') : '<span class="empty-cell">—</span>' ?>
                    </td>
                    <td class="flight-prozvon">
                        <span class="<?= $prozvonClass ?>"><?= $prepFilled ?> / <?= $totalZayavki ?></span>
                    </td>
                    <td class="flight-addr"><?= $uniqueAddr ?> / <?= $totalZayavki ?></td>
                    <td class="flight-weight"><?= number_format((int) $totalMass, 0, '.', ' ') ?> кг</td>
                    <td class="flight-actions">
                        <button type="button" class="action-btn action-btn-neutral"
                                data-requests='<?= $zayavkiJson ?>'
                                onclick="showRequests(this, event)"
                                title="Список заявок">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </button>
                        <button type="button" class="action-btn action-btn-disabled"
                                disabled
                                title="Маршрут будет подключён позже">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </button>
                    </td>
                </tr>
                <!-- Detail row -->
                <tr id="details_<?= $flightId ?>" class="flight-details-row" data-flight-details-for="<?= $flightId ?>" hidden>
                    <td colspan="12" class="flight-details-cell">
                        <div class="flight-details">
                            <table class="flight-details-table">
                                <thead>
                                    <tr>
                                        <th>ID ЗАЯВКИ</th>
                                        <th>МАССА, КГ</th>
                                        <th>ГРУЗООТПРАВИТЕЛЬ</th>
                                        <th>АДРЕС ПОГРУЗКИ</th>
                                        <th>КОНТАКТЫ</th>
                                        <th>ДОГОВОР</th>
                                        <th>ПРОПУСК</th>
                                        <th>КОММЕНТАРИИ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($flight['zayavki'])): ?>
                                        <?php foreach ($flight['zayavki'] as $zi):
                                            $massKg = floatval($zi['mass_netto'] ?? 0) * 1000;
                                            $prepTime = $zi['prep_time'] ?? '';
                                            $comments = $zi['comments'] ?? '';
                                            $contacts = $service->formatContacts($zi);
                                            $contractLabel = $service->getContractLabel($zi['price_per_kg'] ?? null);
                                            $propiskaInfo = $service->getPropiskaLabel($prepTime);
                                            $senderName = $zi['naim_oo_gruzootpravitel'] ?? '';
                                            $address = $zi['mno_adres_pogruzki'] ?? '';
                                        ?>
                                        <tr>
                                            <td class="text-center"><strong><?= htmlspecialchars((string) ($zi['zayavka_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-right"><?= number_format((int) $massKg, 0, '.', ' ') ?></td>
                                            <td class="cell-ellipsis" title="<?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="cell-ellipsis" title="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="contacts-cell">
                                                <?php if (!empty($contacts)): ?>
                                                    <?php foreach ($contacts as $c): ?>
                                                        <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?><br>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="empty-cell">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><strong><?= htmlspecialchars($contractLabel, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-center">
                                                <?php if ($propiskaInfo['label'] === '—'): ?>
                                                    <span class="empty-cell">—</span>
                                                <?php else: ?>
                                                    <span class="<?= htmlspecialchars($propiskaInfo['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($propiskaInfo['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="comments-cell">
                                                <?php if ($comments !== ''): ?>
                                                    <?= nl2br(htmlspecialchars($comments, ENT_QUOTES, 'UTF-8')) ?>
                                                <?php else: ?>
                                                    <span class="empty-cell">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center" style="color: var(--text-faint); padding: 24px;">Данные заявок не найдены</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else:
    // Empty state messages matching legacy
    $emptyMessages = [
        'planned'    => 'Планы на вывоз не найдены',
        'in_transit' => 'Рейсы в пути не найдены',
        'unloaded'   => 'Выгруженные рейсы не найдены',
    ];
    $emptyMsg = $emptyMessages[$tab] ?? 'Рейсы не найдены';
?>
<div class="card" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
    <?= htmlspecialchars($emptyMsg, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<script>
function toggleFlightRow(row) {
    var flightId = row.getAttribute('data-flight-id');
    var detailRow = document.querySelector('[data-flight-details-for="' + flightId + '"]');
    if (!detailRow) return;
    var arrow = row.querySelector('.flight-toggle-arrow');

    if (detailRow.hasAttribute('hidden')) {
        detailRow.removeAttribute('hidden');
        detailRow.style.display = 'table-row';
        row.classList.add('flight-row-expanded');
        row.setAttribute('aria-expanded', 'true');
        if (arrow) arrow.textContent = '▼';
    } else {
        detailRow.setAttribute('hidden', '');
        detailRow.style.display = 'none';
        row.classList.remove('flight-row-expanded');
        row.setAttribute('aria-expanded', 'false');
        if (arrow) arrow.textContent = '▶';
    }
}

// Click delegation — раскрытие по клику на всю строку
document.addEventListener('click', function (event) {
    var interactive = event.target.closest('.action-btn, button, a, input, select, textarea');
    if (interactive) return;

    var row = event.target.closest('.flight-row');
    if (!row) return;

    toggleFlightRow(row);
});

// Keyboard — Enter/Space на строке
document.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;

    var row = event.target.closest('.flight-row');
    if (!row) return;

    event.preventDefault();
    toggleFlightRow(row);
});

function showRequests(btn, ev) {
    ev.stopPropagation();
    ev.preventDefault();
    try {
        var d = JSON.parse(btn.dataset.requests);
        var w = window.open('', 'DeleteWindow', 'width=300,height=300,scrollbars=yes,resizable=yes');
        if (!w) {
            alert('Разрешите всплывающие окна');
            return;
        }
        var h = '<html><head><meta charset="UTF-8"><title>Заявки</title><style>body{font-family:monospace;font-size:14px;margin:10px}table{width:100%;border-collapse:collapse}td{padding:3px 8px}</style></head><body><table><tbody>';
        for (var i = 0; i < d.length; i++) {
            h += '<tr><td>' + d[i].id + '</td><td>' + d[i].weight + '</td></tr>';
        }
        h += '</tbody></table></body></html>';
        w.document.write(h);
        w.document.close();
        w.focus();
    } catch (e) {
        console.error(e);
        alert('Ошибка загрузки данных');
    }
}
</script>
