<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\forms\backend;

use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Форма загрузки DMARC-архивов.
 * Поддерживает загрузку одного или нескольких файлов одновременно.
 */
class UploadForm extends Model
{
    /**
     * Загруженные файлы.
     *
     * @var UploadedFile[]
     */
    public array $files = [];

    public function rules(): array
    {
        return [
            [['files'], 'required', 'message' => 'Выберите хотя бы один файл.'],
            [['files'], 'each', 'rule' => [
                'file',
                'extensions'  => ['zip', 'gz', 'xml'],
                'maxSize'     => 10 * 1024 * 1024, // 10 MB
                'checkExtensionByMimeType' => false,
                'message'     => 'Недопустимый файл.',
            ]],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'files' => 'Файлы архивов (.zip, .gz, .xml)',
        ];
    }

    /**
     * Загружает файлы из POST-запроса (поддержка multiple).
     */
    public function loadFiles(): void
    {
        $this->files = UploadedFile::getInstances($this, 'files');
    }
}
