<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\entities;

use Besnovatyj\Dmarc\entities\queries\DmarcReportRecordQuery;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Запись DMARC-отчёта (одна строка — один IP-адрес).
 *
 * @property int         $id
 * @property int         $dmarc_report_id
 * @property string      $source_ip
 * @property int         $message_count
 * @property string      $disposition        none|quarantine|reject
 * @property string      $dkim_result        pass|fail
 * @property string      $spf_result         pass|fail
 * @property string      $header_from
 * @property string|null $envelope_from
 * @property string|null $auth_dkim_domain
 * @property string|null $auth_dkim_selector
 * @property string|null $auth_dkim_result
 * @property string|null $auth_dkim_extra    JSON
 * @property string|null $auth_spf_domain
 * @property string|null $auth_spf_result
 * @property string      $created_at
 *
 * @property DmarcReport $report
 */
class DmarcReportRecord extends ActiveRecord
{
    /**
     * Фабричный метод создания записи.
     *
     * @param array<int, array{domain: string, selector: string|null, result: string}> $dkimExtra
     */
    public static function create(
        int     $dmarcReportId,
        string  $sourceIp,
        int     $messageCount,
        string  $disposition,
        string  $dkimResult,
        string  $spfResult,
        string  $headerFrom,
        ?string $envelopeFrom,
        ?string $authDkimDomain,
        ?string $authDkimSelector,
        ?string $authDkimResult,
        array   $dkimExtra,
        ?string $authSpfDomain,
        ?string $authSpfResult,
    ): self {
        $record = new static();
        $record->dmarc_report_id   = $dmarcReportId;
        $record->source_ip         = $sourceIp;
        $record->message_count     = $messageCount;
        $record->disposition       = $disposition;
        $record->dkim_result       = $dkimResult;
        $record->spf_result        = $spfResult;
        $record->header_from       = $headerFrom;
        $record->envelope_from     = $envelopeFrom;
        $record->auth_dkim_domain  = $authDkimDomain;
        $record->auth_dkim_selector = $authDkimSelector;
        $record->auth_dkim_result  = $authDkimResult;
        $record->auth_dkim_extra   = $dkimExtra ? json_encode($dkimExtra, JSON_UNESCAPED_UNICODE) : null;
        $record->auth_spf_domain   = $authSpfDomain;
        $record->auth_spf_result   = $authSpfResult;
        return $record;
    }

    // <editor-fold desc="Вычисляемые поля">

    /**
     * Прошла ли запись полную DMARC-проверку (DKIM pass И SPF pass).
     */
    public function isFullPass(): bool
    {
        return $this->dkim_result === 'pass' && $this->spf_result === 'pass';
    }

    /**
     * Является ли запись проблемной (DKIM или SPF fail).
     */
    public function hasProblem(): bool
    {
        return !$this->isFullPass();
    }

    /**
     * Является ли disposition блокирующим (quarantine или reject).
     */
    public function isBlocked(): bool
    {
        return in_array($this->disposition, ['quarantine', 'reject'], true);
    }

    /**
     * CSS-класс Bootstrap для значка результата (pass/fail).
     */
    public static function resultBadgeClass(string $result): string
    {
        return $result === 'pass' ? 'success' : 'danger';
    }

    /**
     * CSS-класс Bootstrap для значка disposition.
     */
    public static function dispositionBadgeClass(string $disposition): string
    {
        return match ($disposition) {
            'reject'     => 'danger',
            'quarantine' => 'warning',
            default      => 'success',
        };
    }

    /**
     * Возвращает дополнительные DKIM-результаты, декодированные из JSON.
     *
     * @return array<int, array{domain: string, selector: string|null, result: string}>
     */
    public function getDkimExtraArray(): array
    {
        if ($this->auth_dkim_extra === null) {
            return [];
        }
        $decoded = json_decode($this->auth_dkim_extra, true);
        return is_array($decoded) ? $decoded : [];
    }

    // </editor-fold>

    // <editor-fold desc="Отношения">

    /** @return ActiveQuery<DmarcReport> */
    public function getReport(): ActiveQuery
    {
        return $this->hasOne(DmarcReport::class, ['id' => 'dmarc_report_id']);
    }

    // </editor-fold>

    public static function tableName(): string
    {
        return '{{%dmarc_report_records}}';
    }

    public static function find(): DmarcReportRecordQuery
    {
        return new DmarcReportRecordQuery(static::class);
    }
}
