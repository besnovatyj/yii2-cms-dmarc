<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\entities;

use Besnovatyj\Dmarc\entities\queries\DmarcReportQuery;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * DMARC aggregate-отчёт.
 *
 * @property int         $id
 * @property string      $report_id           Уникальный ID из XML
 * @property string      $org_name            Организация-отправитель
 * @property string      $contact_email       E-mail
 * @property string|null $extra_contact_info
 * @property string      $date_begin          Начало периода (DATETIME)
 * @property string      $date_end            Конец периода (DATETIME)
 * @property string      $domain              Домен из policy_published
 * @property string      $adkim               r|s
 * @property string      $aspf                r|s
 * @property string      $policy_p            none|quarantine|reject
 * @property string|null $policy_sp
 * @property int         $policy_pct
 * @property string      $source_filename
 * @property string      $created_at
 *
 * @property DmarcReportRecord[] $records
 */
class DmarcReport extends ActiveRecord
{
    /**
     * Фабричный метод создания отчёта из данных парсера.
     */
    public static function create(
        string  $reportId,
        string  $orgName,
        string  $contactEmail,
        ?string $extraContactInfo,
        string  $dateBegin,
        string  $dateEnd,
        string  $domain,
        string  $adkim,
        string  $aspf,
        string  $policyP,
        ?string $policySp,
        int     $policyPct,
        string  $sourceFilename,
    ): self {
        $report = new static();
        $report->report_id          = $reportId;
        $report->org_name           = $orgName;
        $report->contact_email      = $contactEmail;
        $report->extra_contact_info = $extraContactInfo;
        $report->date_begin         = $dateBegin;
        $report->date_end           = $dateEnd;
        $report->domain             = $domain;
        $report->adkim              = $adkim;
        $report->aspf               = $aspf;
        $report->policy_p           = $policyP;
        $report->policy_sp          = $policySp;
        $report->policy_pct         = $policyPct;
        $report->source_filename    = $sourceFilename;
        return $report;
    }

    // <editor-fold desc="Вычисляемые поля">

    /**
     * Общее количество писем по всем записям отчёта.
     */
    public function getTotalMessageCount(): int
    {
        return (int)array_sum(array_map(fn(DmarcReportRecord $r) => $r->message_count, $this->records));
    }

    /**
     * Количество писем, прошедших DKIM и SPF (оба pass).
     */
    public function getFullPassCount(): int
    {
        return (int)array_sum(
            array_map(
                fn(DmarcReportRecord $r) => $r->message_count,
                array_filter(
                    $this->records,
                    fn(DmarcReportRecord $r) => $r->dkim_result === 'pass' && $r->spf_result === 'pass'
                )
            )
        );
    }

    /**
     * Количество писем с проблемами (DKIM или SPF fail).
     */
    public function getFailCount(): int
    {
        return $this->getTotalMessageCount() - $this->getFullPassCount();
    }

    /**
     * Количество писем с disposition = quarantine или reject.
     */
    public function getBlockedCount(): int
    {
        return (int)array_sum(
            array_map(
                fn(DmarcReportRecord $r) => $r->message_count,
                array_filter(
                    $this->records,
                    fn(DmarcReportRecord $r) => in_array($r->disposition, ['quarantine', 'reject'], true)
                )
            )
        );
    }

    /**
     * Процент успешно прошедших DKIM+SPF писем.
     */
    public function getPassPercent(): float
    {
        $total = $this->getTotalMessageCount();
        if ($total === 0) {
            return 100.0;
        }
        return round($this->getFullPassCount() / $total * 100, 1);
    }

    /**
     * Читаемый период: «01.02.2025 – 01.03.2025».
     */
    public function getPeriodLabel(): string
    {
        return date('d.m.Y', strtotime($this->date_begin))
            . ' – '
            . date('d.m.Y', strtotime($this->date_end));
    }

    /**
     * Читаемое название политики.
     */
    public function getPolicyLabel(): string
    {
        return match ($this->policy_p) {
            'reject'     => 'Reject',
            'quarantine' => 'Quarantine',
            default      => 'None',
        };
    }

    /**
     * CSS-класс Bootstrap для значка политики.
     */
    public function getPolicyBadgeClass(): string
    {
        return match ($this->policy_p) {
            'reject'     => 'danger',
            'quarantine' => 'warning',
            default      => 'secondary',
        };
    }

    // </editor-fold>

    // <editor-fold desc="Отношения">

    /** @return ActiveQuery<DmarcReportRecord> */
    public function getRecords(): ActiveQuery
    {
        return $this->hasMany(DmarcReportRecord::class, ['dmarc_report_id' => 'id'])
            ->orderBy(['message_count' => SORT_DESC, 'id' => SORT_ASC]);
    }

    // </editor-fold>

    public static function tableName(): string
    {
        return '{{%dmarc_reports}}';
    }

    public static function find(): DmarcReportQuery
    {
        return new DmarcReportQuery(static::class);
    }

    public function transactions(): array
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
}
