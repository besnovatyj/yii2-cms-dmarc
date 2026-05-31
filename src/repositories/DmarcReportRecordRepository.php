<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\repositories;

use Besnovatyj\Dmarc\entities\DmarcReportRecord;
use RuntimeException;

/**
 * Репозиторий записей DMARC-отчётов.
 */
class DmarcReportRecordRepository
{
    /**
     * Сохраняет запись.
     *
     * @throws RuntimeException при ошибке сохранения
     */
    public function save(DmarcReportRecord $record): void
    {
        if (!$record->save()) {
            throw new RuntimeException(
                'Ошибка сохранения записи отчёта: ' . json_encode($record->getErrors(), JSON_UNESCAPED_UNICODE)
            );
        }
    }
}
