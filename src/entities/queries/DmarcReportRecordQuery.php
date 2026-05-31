<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\entities\queries;

use yii\db\ActiveQuery;

/**
 * Query-класс для DmarcReportRecord с fluent-фильтрами.
 */
class DmarcReportRecordQuery extends ActiveQuery
{
    /**
     * Фильтр по ID отчёта.
     */
    public function byReport(int $reportId): static
    {
        return $this->andWhere(['dmarc_report_id' => $reportId]);
    }

    /**
     * Только записи с DKIM fail.
     */
    public function dkimFail(): static
    {
        return $this->andWhere(['dkim_result' => 'fail']);
    }

    /**
     * Только записи с SPF fail.
     */
    public function spfFail(): static
    {
        return $this->andWhere(['spf_result' => 'fail']);
    }

    /**
     * Только заблокированные записи (quarantine или reject).
     */
    public function blocked(): static
    {
        return $this->andWhere(['in', 'disposition', ['quarantine', 'reject']]);
    }

    /**
     * Сортировка по количеству писем (сначала больше).
     */
    public function mostFirst(): static
    {
        return $this->orderBy(['message_count' => SORT_DESC, 'id' => SORT_ASC]);
    }
}
