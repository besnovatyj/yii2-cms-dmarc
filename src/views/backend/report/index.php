<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Dmarc\entities\DmarcReport;
use Besnovatyj\Dmarc\forms\backend\search\ReportSearch;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this         yii\web\View */
/* @var $searchModel  ReportSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title                   = 'DMARC-отчёты';
$this->params['breadcrumbs'][] = $this->title;

/**
 * Возвращает HTML-значок pass/fail.
 */
$resultBadge = static function (string $result): string {
    $class = $result === 'pass' ? 'success' : 'danger';
    $icon  = $result === 'pass' ? 'check-circle-fill' : 'x-circle-fill';
    return '<span class="badge bg-' . $class . '"><i class="bi bi-' . $icon . ' me-1"></i>'
        . strtoupper($result) . '</span>';
};

/**
 * Возвращает HTML-значок disposition.
 */
$dispositionBadge = static function (string $d): string {
    [$class, $icon] = match ($d) {
        'reject'     => ['danger',  'slash-circle'],
        'quarantine' => ['warning', 'exclamation-triangle'],
        default      => ['success', 'check-circle'],
    };
    return '<span class="badge bg-' . $class . '"><i class="bi bi-' . $icon . ' me-1"></i>' . ucfirst($d) . '</span>';
};
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-check me-2"></i>Список DMARC-отчётов</span>
        <?= Html::a(
            '<i class="bi bi-upload me-1"></i>Загрузить архив',
            ['/Dmarc/backend/upload/index'],
            ['class' => 'btn btn-primary btn-sm']
        ) ?>
    </div>

    <?php /* Панель фильтров */ ?>
    <div class="card-body border-bottom py-3">
        <?php
        $formAction = \yii\helpers\Url::to(['/Dmarc/backend/report/index']);
        ?>
        <form method="get" action="<?= $formAction ?>" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Организация</label>
                <?= Html::activeDropDownList(
                    $searchModel, 'org_name',
                    $searchModel->orgNamesList(),
                    ['class' => 'form-select form-select-sm', 'prompt' => 'Все организации']
                ) ?>
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Домен</label>
                <?= Html::activeDropDownList(
                    $searchModel, 'domain',
                    $searchModel->domainsList(),
                    ['class' => 'form-select form-select-sm', 'prompt' => 'Все домены']
                ) ?>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Период с</label>
                <?= Html::activeInput('date', $searchModel, 'date_from', ['class' => 'form-control form-control-sm']) ?>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Период по</label>
                <?= Html::activeInput('date', $searchModel, 'date_to', ['class' => 'form-control form-control-sm']) ?>
            </div>
            <div class="col-sm-2 d-flex flex-column gap-1">
                <div class="form-check form-check-sm">
                    <?= Html::activeCheckbox($searchModel, 'only_failures', [
                        'class' => 'form-check-input',
                        'id'    => 'only_failures',
                    ]) ?>
                    <label class="form-check-label small" for="only_failures">Только с ошибками</label>
                </div>
                <div class="form-check form-check-sm">
                    <?= Html::activeCheckbox($searchModel, 'only_blocked', [
                        'class' => 'form-check-input',
                        'id'    => 'only_blocked',
                    ]) ?>
                    <label class="form-check-label small" for="only_blocked">Только с блокировкой</label>
                </div>
            </div>
            <div class="col-auto d-flex gap-2 ms-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Применить
                </button>
                <?= Html::a('<i class="bi bi-x-lg me-1"></i>Сбросить', ['/Dmarc/backend/report/index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel'  => null,
            'layout'       => "{summary}\n{items}",
            'tableOptions' => ['class' => 'table table-hover table-bordered mb-0'],
            'rowOptions'   => static function (DmarcReport $report) {
                // Подсвечиваем красным строки с полными ошибками
                if ($report->getFailCount() > 0 && $report->getBlockedCount() > 0) {
                    return ['class' => 'table-danger'];
                }
                if ($report->getFailCount() > 0) {
                    return ['class' => 'table-warning'];
                }
                return [];
            },
            'columns' => [
                [
                    'label'          => 'Период',
                    'value'          => static fn(DmarcReport $r) => $r->getPeriodLabel(),
                    'contentOptions' => ['style' => 'white-space:nowrap; width:170px'],
                ],
                [
                    'attribute' => 'org_name',
                    'label'     => 'Организация',
                ],
                [
                    'attribute' => 'domain',
                    'label'     => 'Домен',
                    'value'     => static fn(DmarcReport $r) => $r->domain,
                ],
                [
                    'label'          => 'Политика',
                    'format'         => 'raw',
                    'value'          => static fn(DmarcReport $r) => '<span class="badge bg-'
                        . $r->getPolicyBadgeClass() . '">'
                        . $r->getPolicyLabel() . '</span>',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'width:100px'],
                    'headerOptions'  => ['class' => 'text-center'],
                ],
                [
                    'label'          => 'Писем',
                    'value'          => static fn(DmarcReport $r) => number_format($r->getTotalMessageCount()),
                    'contentOptions' => ['class' => 'text-end', 'style' => 'width:80px'],
                    'headerOptions'  => ['class' => 'text-end'],
                ],
                [
                    'label'          => 'Pass%',
                    'format'         => 'raw',
                    'value'          => static function (DmarcReport $r) {
                        $pct   = $r->getPassPercent();
                        $color = $pct >= 95 ? 'success' : ($pct >= 70 ? 'warning' : 'danger');
                        return '<div class="d-flex align-items-center gap-2">'
                            . '<div class="progress flex-grow-1" style="height:8px">'
                            . '<div class="progress-bar bg-' . $color . '" style="width:' . $pct . '%"></div>'
                            . '</div>'
                            . '<span class="text-' . $color . ' fw-semibold" style="width:40px">' . $pct . '%</span>'
                            . '</div>';
                    },
                    'contentOptions' => ['style' => 'min-width:130px'],
                ],
                [
                    'label'          => 'Fail',
                    'format'         => 'raw',
                    'value'          => static function (DmarcReport $r) {
                        $fail = $r->getFailCount();
                        if ($fail === 0) {
                            return '<span class="text-success">—</span>';
                        }
                        return '<span class="badge bg-danger">' . number_format($fail) . '</span>';
                    },
                    'contentOptions' => ['class' => 'text-center', 'style' => 'width:70px'],
                    'headerOptions'  => ['class' => 'text-center'],
                ],
                [
                    'label'          => 'Blocked',
                    'format'         => 'raw',
                    'value'          => static function (DmarcReport $r) {
                        $blocked = $r->getBlockedCount();
                        if ($blocked === 0) {
                            return '<span class="text-success">—</span>';
                        }
                        return '<span class="badge bg-warning text-dark">' . number_format($blocked) . '</span>';
                    },
                    'contentOptions' => ['class' => 'text-center', 'style' => 'width:80px'],
                    'headerOptions'  => ['class' => 'text-center'],
                ],
                [
                    'class'          => ActionColumn::class,
                    'template'       => '{view} {delete}',
                    'buttons'        => [
                        'view'   => static fn($url, DmarcReport $r) => Html::a(
                            '<i class="bi bi-eye"></i>',
                            ['view', 'id' => $r->id],
                            ['class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => 'Просмотр']
                        ),
                        'delete' => static fn($url, DmarcReport $r) => Html::a(
                            '<i class="bi bi-trash"></i>',
                            ['delete', 'id' => $r->id],
                            [
                                'class'        => 'btn btn-sm btn-outline-danger',
                                'title'        => 'Удалить',
                                'data-confirm' => "Удалить отчёт «{$r->org_name} / {$r->getPeriodLabel()}»?",
                                'data-method'  => 'post',
                            ]
                        ),
                    ],
                    'contentOptions' => ['style' => 'width:90px; white-space:nowrap'],
                ],
            ],
        ]) ?>
    </div>
    <div class="card-footer">
        <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
    </div>
</div>
