<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Dmarc\forms\backend\UploadForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/* @var $this  yii\web\View */
/* @var $model UploadForm */

$this->title                   = 'Загрузить DMARC-отчёт';
$this->params['breadcrumbs'][] = ['label' => 'Отчёты DMARC', 'url' => ['/Dmarc/backend/report/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-upload me-2"></i>Загрузить архивы с DMARC-отчётами
            </div>
            <div class="card-body">

                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Поддерживаемые форматы:</strong>
                    <ul class="mb-0 mt-1">
                        <li><code>.zip</code> — ZIP-архив, может содержать несколько XML-файлов</li>
                        <li><code>.gz</code> — GZ-архив с одним XML-файлом</li>
                        <li><code>.xml</code> — сырой XML-файл отчёта</li>
                    </ul>
                    Можно загружать несколько файлов одновременно. Дубликаты будут пропущены.
                </div>

                <?php $form = ActiveForm::begin([
                    'id'      => 'upload-form',
                    'options' => ['enctype' => 'multipart/form-data'],
                ]) ?>

                <div class="mb-4">
                    <?= $form->field($model, 'files[]')->fileInput([
                        'multiple'  => true,
                        'accept'    => '.zip,.gz,.xml',
                        'class'     => 'form-control',
                        'id'        => 'upload-files',
                    ])->label('Файлы архивов') ?>
                    <div class="form-text text-muted">
                        Удерживайте <kbd>Ctrl</kbd> (или <kbd>Cmd</kbd> на Mac) для выбора нескольких файлов.
                        Максимальный размер каждого файла: 10 МБ.
                    </div>
                </div>

                <div id="file-preview" class="mb-3" style="display:none">
                    <p class="fw-semibold mb-2">Выбранные файлы:</p>
                    <ul id="file-list" class="list-group list-group-flush mb-0"></ul>
                </div>

                <div class="d-flex gap-2">
                    <?= Html::submitButton(
                        '<i class="bi bi-cloud-upload me-1"></i>Импортировать',
                        ['class' => 'btn btn-primary']
                    ) ?>
                    <?= Html::a(
                        '<i class="bi bi-list-ul me-1"></i>К списку отчётов',
                        ['/Dmarc/backend/report/index'],
                        ['class' => 'btn btn-outline-secondary']
                    ) ?>
                </div>

                <?php ActiveForm::end() ?>

            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
document.getElementById('upload-files').addEventListener('change', function () {
    const preview = document.getElementById('file-preview');
    const list    = document.getElementById('file-list');
    list.innerHTML = '';
    if (this.files.length === 0) {
        preview.style.display = 'none';
        return;
    }
    for (const file of this.files) {
        const sizeMb = (file.size / 1048576).toFixed(2);
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center px-0 py-1';
        li.innerHTML = '<span><i class="bi bi-file-zip me-2 text-muted"></i>' + file.name + '</span>'
                     + '<span class="badge bg-secondary">' + sizeMb + ' МБ</span>';
        list.appendChild(li);
    }
    preview.style.display = 'block';
});
JS;
$this->registerJs($js);
?>
