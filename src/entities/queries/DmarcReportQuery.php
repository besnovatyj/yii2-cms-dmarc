<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\entities\queries;

use yii\db\ActiveQuery;

/**
 * Query-класс для DmarcReport с fluent-фильтрами.
 */
class DmarcReportQuery extends ActiveQuery
{
    /**
     * Фильтр по организации (частичное совпадение).
     */
    public function byOrgName(string $orgName): static
    {
        return $this->andWhere(['like', 'org_name', $orgName]);
    }

    /**
     * Фильтр по домену (точное совпадение).
     */
    public function byDomain(string $domain): static
    {
        return $this->andWhere(['domain' => $domain]);
    }

    /**
     * Фильтр: период начинается не раньше даты.
     */
    public function dateBeginFrom(string $date): static
    {
        return $this->andWhere(['>=', 'date_begin', $date]);
    }

    /**
     * Фильтр: период заканчивается не позже даты.
     */
    public function dateEndTo(string $date): static
    {
        return $this->andWhere(['<=', 'date_end', $date]);
    }

    /**
     * Только отчёты с хотя бы одной провальной записью (DKIM или SPF fail).
     *
     * Используем innerJoin вместо innerJoinWith, чтобы не подмешивался orderBy
     * из relation getRecords() — иначе MySQL отказывает при DISTINCT + ORDER BY
     * по столбцу из join-таблицы, не входящему в SELECT.
     */
    public function withFailures(): static
    {
        return $this
            ->innerJoin(
                '{{%dmarc_report_records}}',
                '{{%dmarc_report_records}}.dmarc_report_id = {{%dmarc_reports}}.id'
            )
            ->andWhere(['or',
                ['{{%dmarc_report_records}}.dkim_result' => 'fail'],
                ['{{%dmarc_report_records}}.spf_result'  => 'fail'],
            ])
            ->distinct();
    }

    /**
     * Только отчёты с хотя бы одной блокировкой (quarantine/reject).
     *
     * Используем innerJoin вместо innerJoinWith, чтобы не подмешивался orderBy
     * из relation getRecords() — иначе MySQL отказывает при DISTINCT + ORDER BY
     * по столбцу из join-таблицы, не входящему в SELECT.
     */
    public function withBlocked(): static
    {
        return $this
            ->innerJoin(
                '{{%dmarc_report_records}}',
                '{{%dmarc_report_records}}.dmarc_report_id = {{%dmarc_reports}}.id'
            )
            ->andWhere(['in', '{{%dmarc_report_records}}.disposition', ['quarantine', 'reject']])
            ->distinct();
    }

    /**
     * Сортировка: сначала новые (по дате начала периода).
     */
    public function latestFirst(): static
    {
        return $this->orderBy(['date_begin' => SORT_DESC, 'id' => SORT_DESC]);
    }
}
