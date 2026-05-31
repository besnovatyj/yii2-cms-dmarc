<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Dmarc\entities\DmarcReport;
use Besnovatyj\Dmarc\entities\DmarcReportRecord;
use yii\helpers\Html;

/* @var $this   yii\web\View */
/* @var $report DmarcReport */

$this->title = 'Отчёт: ' . $report->org_name . ' / ' . $report->getPeriodLabel();
$this->params['breadcrumbs'][] = ['label' => 'DMARC-отчёты', 'url' => ['/Dmarc/backend/report/index']];
$this->params['breadcrumbs'][] = $this->title;

$total   = $report->getTotalMessageCount();
$pass    = $report->getFullPassCount();
$fail    = $report->getFailCount();
$blocked = $report->getBlockedCount();
$pct     = $report->getPassPercent();

/**
 * Возвращает Bootstrap-значок для результата pass/fail.
 */
$resultBadge = static function (string $result): string {
    if ($result === 'pass') {
        return '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>PASS</span>';
    }
    return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>FAIL</span>';
};

/**
 * Возвращает Bootstrap-значок для disposition.
 */
$dispositionBadge = static function (string $d): string {
    return match ($d) {
        'reject'     => '<span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Reject</span>',
        'quarantine' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Quarantine</span>',
        default      => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>None</span>',
    };
};

/**
 * Режим выравнивания DKIM/SPF.
 */
$alignmentLabel = static fn(string $a) => $a === 's'
    ? '<span class="badge bg-info text-dark">Strict</span>'
    : '<span class="badge bg-secondary">Relaxed</span>';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <?= Html::a('<i class="bi bi-arrow-left me-1"></i>К списку', ['/Dmarc/backend/report/index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
    </div>
    <div>
        <?= Html::a(
            '<i class="bi bi-trash me-1"></i>Удалить отчёт',
            ['delete', 'id' => $report->id],
            [
                'class'        => 'btn btn-outline-danger btn-sm',
                'data-confirm' => "Удалить этот отчёт и все его записи?",
                'data-method'  => 'post',
            ]
        ) ?>
    </div>
</div>

<?php /* ======== Метаданные отчёта ======== */ ?>
<div class="row g-3 mb-4">

    <?php /* Основные данные */ ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-info-circle me-2"></i>Метаданные отчёта
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                    <tr>
                        <th class="text-nowrap" style="width:140px">Организация</th>
                        <td><?= Html::encode($report->org_name) ?></td>
                    </tr>
                    <tr>
                        <th>Контакт</th>
                        <td><?= Html::encode($report->contact_email) ?></td>
                    </tr>
                    <?php if ($report->extra_contact_info): ?>
                    <tr>
                        <th>Доп. контакт</th>
                        <td><?= Html::encode($report->extra_contact_info) ?></td>
                    </tr>
                    <?php endif ?>
                    <tr>
                        <th>ID отчёта</th>
                        <td><code><?= Html::encode($report->report_id) ?></code></td>
                    </tr>
                    <tr>
                        <th>Период</th>
                        <td><?= Html::encode($report->getPeriodLabel()) ?></td>
                    </tr>
                    <tr>
                        <th>Файл-источник</th>
                        <td><code class="text-muted"><?= Html::encode($report->source_filename) ?></code></td>
                    </tr>
                    <tr>
                        <th>Импортирован</th>
                        <td><?= date('d.m.Y H:i', strtotime($report->created_at)) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php /* Политика */ ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-shield me-2"></i>Политика DMARC (policy_published)
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                    <tr>
                        <th class="text-nowrap" style="width:140px">Домен</th>
                        <td><strong><?= Html::encode($report->domain) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Политика (p)</th>
                        <td>
                            <span class="badge bg-<?= $report->getPolicyBadgeClass() ?>">
                                <?= $report->getPolicyLabel() ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($report->policy_sp): ?>
                    <tr>
                        <th>Поддомены (sp)</th>
                        <td>
                            <?php
                            $spClass = match ($report->policy_sp) {
                                'reject'     => 'danger',
                                'quarantine' => 'warning',
                                default      => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?= $spClass ?>">
                                <?= ucfirst(Html::encode($report->policy_sp)) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif ?>
                    <tr>
                        <th>Процент (pct)</th>
                        <td><?= $report->policy_pct ?>%</td>
                    </tr>
                    <tr>
                        <th>DKIM выравнивание</th>
                        <td><?= $alignmentLabel($report->adkim) ?></td>
                    </tr>
                    <tr>
                        <th>SPF выравнивание</th>
                        <td><?= $alignmentLabel($report->aspf) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php /* ======== Сводная статистика ======== */ ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center border-secondary">
            <div class="card-body py-3">
                <div class="display-6 fw-bold"><?= number_format($total) ?></div>
                <div class="text-muted small">Всего писем</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center border-success">
            <div class="card-body py-3">
                <div class="display-6 fw-bold text-success"><?= number_format($pass) ?></div>
                <div class="text-muted small">DKIM+SPF pass</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center <?= $fail > 0 ? 'border-danger' : 'border-success' ?>">
            <div class="card-body py-3">
                <div class="display-6 fw-bold <?= $fail > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= number_format($fail) ?>
                </div>
                <div class="text-muted small">С ошибками</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center <?= $blocked > 0 ? 'border-warning' : 'border-success' ?>">
            <div class="card-body py-3">
                <div class="display-6 fw-bold <?= $blocked > 0 ? 'text-warning' : 'text-success' ?>">
                    <?= number_format($blocked) ?>
                </div>
                <div class="text-muted small">Заблокировано</div>
            </div>
        </div>
    </div>
</div>

<?php /* Pass% progress bar */ ?>
<?php
$barColor = $pct >= 95 ? 'success' : ($pct >= 70 ? 'warning' : 'danger');
?>
<div class="card mb-4">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="text-muted">Процент прохождения DMARC (DKIM+SPF pass)</small>
            <strong class="text-<?= $barColor ?>"><?= $pct ?>%</strong>
        </div>
        <div class="progress" style="height:12px">
            <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%"
                 role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
    </div>
</div>

<?php /* ======== Таблица записей ======== */ ?>
<div class="card">
    <div class="card-header fw-semibold">
        <i class="bi bi-table me-2"></i>Записи отчёта (<?= count($report->records) ?> IP-адресов)
    </div>
    <div class="card-body p-0">
        <?php if (empty($report->records)): ?>
            <div class="p-4 text-muted text-center">Записей нет.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>IP-адрес</th>
                        <th class="text-end">Писем</th>
                        <th class="text-center">Disposition</th>
                        <th class="text-center">DKIM<br><small class="fw-normal text-muted">(политика)</small></th>
                        <th class="text-center">SPF<br><small class="fw-normal text-muted">(политика)</small></th>
                        <th>Header From</th>
                        <th>DKIM-подпись</th>
                        <th>SPF-домен</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($report->records as $record): ?>
                    <?php
                    $rowClass = '';
                    if ($record->isBlocked()) {
                        $rowClass = 'table-danger';
                    } elseif ($record->hasProblem()) {
                        $rowClass = 'table-warning';
                    }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td>
                            <code><?= Html::encode($record->source_ip) ?></code>
                        </td>
                        <td class="text-end fw-semibold">
                            <?= number_format($record->message_count) ?>
                        </td>
                        <td class="text-center">
                            <?= $dispositionBadge($record->disposition) ?>
                        </td>
                        <td class="text-center">
                            <?= $resultBadge($record->dkim_result) ?>
                        </td>
                        <td class="text-center">
                            <?= $resultBadge($record->spf_result) ?>
                        </td>
                        <td>
                            <code class="text-muted"><?= Html::encode($record->header_from) ?></code>
                            <?php if ($record->envelope_from && $record->envelope_from !== $record->header_from): ?>
                                <br><small class="text-muted">envelope: <?= Html::encode($record->envelope_from) ?></small>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($record->auth_dkim_domain): ?>
                                <div>
                                    <span class="badge bg-<?= DmarcReportRecord::resultBadgeClass($record->auth_dkim_result ?? 'fail') ?> me-1">
                                        <?= strtoupper($record->auth_dkim_result ?? '?') ?>
                                    </span>
                                    <code class="small"><?= Html::encode($record->auth_dkim_domain) ?></code>
                                    <?php if ($record->auth_dkim_selector): ?>
                                        <small class="text-muted ms-1">(<?= Html::encode($record->auth_dkim_selector) ?>)</small>
                                    <?php endif ?>
                                </div>
                                <?php foreach ($record->getDkimExtraArray() as $extra): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-<?= DmarcReportRecord::resultBadgeClass($extra['result']) ?> me-1">
                                            <?= strtoupper($extra['result']) ?>
                                        </span>
                                        <code class="small"><?= Html::encode($extra['domain']) ?></code>
                                        <?php if ($extra['selector']): ?>
                                            <small class="text-muted ms-1">(<?= Html::encode($extra['selector']) ?>)</small>
                                        <?php endif ?>
                                    </div>
                                <?php endforeach ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php if ($record->auth_spf_domain): ?>
                                <span class="badge bg-<?= DmarcReportRecord::resultBadgeClass($record->auth_spf_result ?? 'fail') ?> me-1">
                                    <?= strtoupper($record->auth_spf_result ?? '?') ?>
                                </span>
                                <code class="small"><?= Html::encode($record->auth_spf_domain) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
    <?php if (!empty($report->records)): ?>
    <div class="card-footer text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Disposition</strong>: none — письма доставлены без изменений; quarantine — помещены в спам; reject — отклонены.
        <strong>DKIM/SPF (политика)</strong> — итоговый вывод DMARC на основе результатов аутентификации и настроек выравнивания.
    </div>
    <?php endif ?>
</div>
