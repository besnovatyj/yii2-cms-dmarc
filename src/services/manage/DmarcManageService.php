<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\services\manage;

use Besnovatyj\Dmarc\dto\ParsedReport;
use Besnovatyj\Dmarc\dto\UploadResult;
use Besnovatyj\Dmarc\entities\DmarcReport;
use Besnovatyj\Dmarc\entities\DmarcReportRecord;
use Besnovatyj\Dmarc\repositories\DmarcReportRecordRepository;
use Besnovatyj\Dmarc\repositories\DmarcReportRepository;
use Besnovatyj\Dmarc\repositories\NotFoundException;
use Besnovatyj\Dmarc\services\parser\DmarcXmlParser;
use Throwable;
use Yii;
use yii\web\UploadedFile;

/**
 * Сервис управления DMARC-отчётами.
 * Обеспечивает загрузку, парсинг, сохранение и удаление отчётов.
 */
class DmarcManageService
{
    public function __construct(
        private readonly DmarcXmlParser              $parser,
        private readonly DmarcReportRepository       $reports,
        private readonly DmarcReportRecordRepository $records,
    ) {
    }

    /**
     * Обрабатывает массив загруженных файлов-архивов.
     * Поддерживает .zip (может содержать несколько XML), .gz и .xml.
     *
     * @param UploadedFile[] $files
     */
    public function uploadFiles(array $files): UploadResult
    {
        $result = new UploadResult();

        foreach ($files as $file) {
            try {
                $parsedReports = $this->parser->parseUploadedFile($file);

                foreach ($parsedReports as $parsed) {
                    if ($this->reports->existsByReportId($parsed->reportId)) {
                        $result->addSkipped($parsed->reportId);
                        continue;
                    }

                    $this->importParsedReport($parsed, $file->name);
                    $result->imported++;
                }
            } catch (Throwable $e) {
                $result->addError($file->name, $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Удаляет отчёт по ID.
     *
     * @throws NotFoundException если отчёт не найден
     * @throws Throwable
     */
    public function remove(int $id): void
    {
        $report = $this->reports->get($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->reports->remove($report);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    // ===== Приватные методы =====

    /**
     * Сохраняет один распарсенный отчёт в БД (транзакционно).
     *
     * @throws Throwable
     */
    private function importParsedReport(ParsedReport $parsed, string $sourceFilename): void
    {
        $report = DmarcReport::create(
            reportId:         $parsed->reportId,
            orgName:          $parsed->orgName,
            contactEmail:     $parsed->contactEmail,
            extraContactInfo: $parsed->extraContactInfo,
            dateBegin:        $parsed->dateBegin,
            dateEnd:          $parsed->dateEnd,
            domain:           $parsed->domain,
            adkim:            $parsed->adkim,
            aspf:             $parsed->aspf,
            policyP:          $parsed->policyP,
            policySp:         $parsed->policySp,
            policyPct:        $parsed->policyPct,
            sourceFilename:   $sourceFilename,
        );

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->reports->save($report);

            foreach ($parsed->records as $parsedRecord) {
                $record = DmarcReportRecord::create(
                    dmarcReportId:    $report->id,
                    sourceIp:         $parsedRecord->sourceIp,
                    messageCount:     $parsedRecord->messageCount,
                    disposition:      $parsedRecord->disposition,
                    dkimResult:       $parsedRecord->dkimResult,
                    spfResult:        $parsedRecord->spfResult,
                    headerFrom:       $parsedRecord->headerFrom,
                    envelopeFrom:     $parsedRecord->envelopeFrom,
                    authDkimDomain:   $parsedRecord->authDkimDomain,
                    authDkimSelector: $parsedRecord->authDkimSelector,
                    authDkimResult:   $parsedRecord->authDkimResult,
                    dkimExtra:        $parsedRecord->dkimExtra,
                    authSpfDomain:    $parsedRecord->authSpfDomain,
                    authSpfResult:    $parsedRecord->authSpfResult,
                );
                $this->records->save($record);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
