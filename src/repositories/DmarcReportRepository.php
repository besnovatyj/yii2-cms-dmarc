<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\repositories;

use Besnovatyj\Dmarc\entities\DmarcReport;
use RuntimeException;
use Throwable;

/**
 * Репозиторий DMARC-отчётов — операции CRUD над сущностью DmarcReport.
 */
class DmarcReportRepository
{
    /**
     * Находит отчёт по ID.
     *
     * @throws NotFoundException если отчёт не найден
     */
    public function get(int $id): DmarcReport
    {
        if (($report = DmarcReport::findOne($id)) === null) {
            throw new NotFoundException("Отчёт с ID={$id} не найден.");
        }

        return $report;
    }

    /**
     * Проверяет, существует ли отчёт с таким report_id (для защиты от дубликатов).
     */
    public function existsByReportId(string $reportId): bool
    {
        return DmarcReport::find()->andWhere(['report_id' => $reportId])->exists();
    }

    /**
     * Сохраняет отчёт.
     *
     * @throws RuntimeException при ошибке сохранения
     */
    public function save(DmarcReport $report): void
    {
        if (!$report->save()) {
            throw new RuntimeException(
                'Ошибка сохранения отчёта: ' . json_encode($report->getErrors(), JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * Удаляет отчёт (записи удаляются каскадно через FK).
     *
     * @throws RuntimeException при ошибке удаления
     * @throws Throwable
     */
    public function remove(DmarcReport $report): void
    {
        if (!$report->delete()) {
            throw new RuntimeException('Ошибка удаления отчёта.');
        }
    }
}
