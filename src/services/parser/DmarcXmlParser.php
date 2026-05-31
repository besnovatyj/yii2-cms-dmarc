<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\services\parser;

use Besnovatyj\Dmarc\dto\ParsedRecord;
use Besnovatyj\Dmarc\dto\ParsedReport;
use RuntimeException;
use SimpleXMLElement;
use yii\web\UploadedFile;

/**
 * Парсер DMARC aggregate-отчётов.
 *
 * Поддерживаемые форматы:
 *  - ZIP-архив (.zip), содержащий один или несколько XML-файлов
 *  - Gzip-архив (.gz), содержащий один XML-файл
 *  - Сырой XML (.xml)
 */
class DmarcXmlParser
{
    /**
     * Извлекает и парсит все XML-отчёты из загруженного файла.
     *
     * @return ParsedReport[] Список распарсенных отчётов (может быть >1 для ZIP с несколькими XML)
     *
     * @throws RuntimeException при невозможности прочитать файл или распарсить XML
     */
    public function parseUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => $this->parseZip($file->tempName, $file->name),
            'gz'  => [$this->parseGz($file->tempName, $file->name)],
            'xml' => [$this->parseXmlFile($file->tempName, $file->name)],
            default => throw new RuntimeException(
                "Неподдерживаемый формат файла «{$file->name}». Ожидается .zip, .gz или .xml."
            ),
        };
    }

    /**
     * Парсит ZIP-архив, возвращая отчёты для каждого найденного XML-файла.
     *
     * @return ParsedReport[]
     */
    private function parseZip(string $tempPath, string $originalName): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException('Расширение PHP ext-zip не установлено.');
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($tempPath);

        if ($opened !== true) {
            throw new RuntimeException("Не удалось открыть ZIP-архив «{$originalName}» (код: {$opened}).");
        }

        $reports = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }

                $entryName = $stat['name'];
                $entryExt  = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));

                // Пропускаем директории и не-XML/GZ файлы
                if (!in_array($entryExt, ['xml', 'gz'], true)) {
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    throw new RuntimeException("Не удалось прочитать файл «{$entryName}» из архива «{$originalName}».");
                }

                if ($entryExt === 'gz') {
                    $content = $this->decompressGz($content, $entryName);
                }

                $reports[] = $this->parseXmlContent($content, $originalName);
            }
        } finally {
            $zip->close();
        }

        if ($reports === []) {
            throw new RuntimeException("В архиве «{$originalName}» не найдено ни одного XML-файла.");
        }

        return $reports;
    }

    /**
     * Парсит GZ-файл.
     */
    private function parseGz(string $tempPath, string $originalName): ParsedReport
    {
        $handle = @gzopen($tempPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть GZ-файл «{$originalName}».");
        }

        $content = '';
        try {
            while (!gzeof($handle)) {
                $chunk = gzread($handle, 65536);
                if ($chunk === false) {
                    throw new RuntimeException("Ошибка чтения GZ-файла «{$originalName}».");
                }
                $content .= $chunk;
            }
        } finally {
            gzclose($handle);
        }

        return $this->parseXmlContent($content, $originalName);
    }

    /**
     * Парсит XML-файл с диска.
     */
    private function parseXmlFile(string $tempPath, string $originalName): ParsedReport
    {
        $content = file_get_contents($tempPath);
        if ($content === false) {
            throw new RuntimeException("Не удалось прочитать файл «{$originalName}».");
        }

        return $this->parseXmlContent($content, $originalName);
    }

    /**
     * Декомпрессирует GZ-данные из строки (для файлов внутри ZIP).
     */
    private function decompressGz(string $data, string $entryName): string
    {
        $result = gzdecode($data);
        if ($result === false) {
            throw new RuntimeException("Не удалось декомпрессировать GZ-файл «{$entryName}».");
        }
        return $result;
    }

    /**
     * Парсит XML-строку и возвращает ParsedReport.
     *
     * @throws RuntimeException при некорректном XML или отсутствии обязательных полей
     */
    private function parseXmlContent(string $xmlContent, string $sourceFilename): ParsedReport
    {
        // Отключаем вывод PHP-ошибок для simplexml, используем исключения
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $msg = implode('; ', array_map(fn(\LibXMLError $e) => trim($e->message), $errors));
            throw new RuntimeException("Ошибка разбора XML из «{$sourceFilename}»: {$msg}");
        }

        libxml_clear_errors();

        return new ParsedReport(
            reportId:         $this->requireString($xml, 'report_metadata/report_id', $sourceFilename),
            orgName:          $this->requireString($xml, 'report_metadata/org_name', $sourceFilename),
            contactEmail:     $this->getString($xml, 'report_metadata/email') ?? '',
            extraContactInfo: $this->getString($xml, 'report_metadata/extra_contact_info'),
            dateBegin:        $this->parseUnixToDatetime(
                                  (int)($xml->report_metadata->date_range->begin ?? 0),
                                  $sourceFilename
                              ),
            dateEnd:          $this->parseUnixToDatetime(
                                  (int)($xml->report_metadata->date_range->end ?? 0),
                                  $sourceFilename
                              ),
            domain:           $this->requireString($xml, 'policy_published/domain', $sourceFilename),
            adkim:            $this->getString($xml, 'policy_published/adkim') ?? 'r',
            aspf:             $this->getString($xml, 'policy_published/aspf') ?? 'r',
            policyP:          $this->getString($xml, 'policy_published/p') ?? 'none',
            policySp:         $this->getString($xml, 'policy_published/sp'),
            policyPct:        (int)($xml->policy_published->pct ?? 100),
            records:          $this->parseRecords($xml),
        );
    }

    /**
     * Парсит все <record> элементы из XML.
     *
     * @return ParsedRecord[]
     */
    private function parseRecords(SimpleXMLElement $xml): array
    {
        $records = [];

        foreach ($xml->record as $record) {
            $row = $record->row;

            // Auth results — DKIM (может быть несколько)
            $authDkimDomain   = null;
            $authDkimSelector = null;
            $authDkimResult   = null;
            $dkimExtra        = [];

            if (isset($record->auth_results->dkim)) {
                $first = true;
                foreach ($record->auth_results->dkim as $dkim) {
                    if ($first) {
                        $authDkimDomain   = $this->normalizeStr((string)$dkim->domain) ?: null;
                        $authDkimSelector = $this->normalizeStr((string)($dkim->selector ?? '')) ?: null;
                        $authDkimResult   = $this->normalizeStr((string)$dkim->result) ?: null;
                        $first = false;
                    } else {
                        $dkimExtra[] = [
                            'domain'   => $this->normalizeStr((string)$dkim->domain),
                            'selector' => $this->normalizeStr((string)($dkim->selector ?? '')) ?: null,
                            'result'   => $this->normalizeStr((string)$dkim->result),
                        ];
                    }
                }
            }

            // Auth results — SPF (обычно один)
            $authSpfDomain = null;
            $authSpfResult = null;
            if (isset($record->auth_results->spf)) {
                $authSpfDomain = $this->normalizeStr((string)$record->auth_results->spf->domain) ?: null;
                $authSpfResult = $this->normalizeStr((string)$record->auth_results->spf->result) ?: null;
            }

            $records[] = new ParsedRecord(
                sourceIp:         $this->normalizeStr((string)($row->source_ip ?? '')),
                messageCount:     max(1, (int)($row->count ?? 1)),
                disposition:      $this->normalizeStr((string)($row->policy_evaluated->disposition ?? 'none')),
                dkimResult:       $this->normalizeStr((string)($row->policy_evaluated->dkim ?? 'fail')),
                spfResult:        $this->normalizeStr((string)($row->policy_evaluated->spf ?? 'fail')),
                headerFrom:       $this->normalizeStr((string)($record->identifiers->header_from ?? '')),
                envelopeFrom:     $this->normalizeStr((string)($record->identifiers->envelope_from ?? '')) ?: null,
                authDkimDomain:   $authDkimDomain,
                authDkimSelector: $authDkimSelector,
                authDkimResult:   $authDkimResult,
                dkimExtra:        $dkimExtra,
                authSpfDomain:    $authSpfDomain,
                authSpfResult:    $authSpfResult,
            );
        }

        return $records;
    }

    // ===== Вспомогательные методы =====

    /**
     * Возвращает строковое значение из XML по XPath-пути, бросает исключение если поле пустое.
     */
    private function requireString(SimpleXMLElement $xml, string $path, string $source): string
    {
        $parts  = explode('/', $path);
        $node   = $xml;
        foreach ($parts as $part) {
            if (!isset($node->$part)) {
                throw new RuntimeException("В файле «{$source}» отсутствует обязательное поле <{$path}>.");
            }
            $node = $node->$part;
        }
        $value = trim((string)$node);
        if ($value === '') {
            throw new RuntimeException("В файле «{$source}» поле <{$path}> пустое.");
        }
        return $value;
    }

    /**
     * Возвращает строковое значение из XML по XPath-пути или null.
     */
    private function getString(SimpleXMLElement $xml, string $path): ?string
    {
        $parts = explode('/', $path);
        $node  = $xml;
        foreach ($parts as $part) {
            if (!isset($node->$part)) {
                return null;
            }
            $node = $node->$part;
        }
        $value = trim((string)$node);
        return $value !== '' ? $value : null;
    }

    /**
     * Преобразует UNIX-timestamp в строку 'Y-m-d H:i:s'.
     */
    private function parseUnixToDatetime(int $timestamp, string $source): string
    {
        if ($timestamp <= 0) {
            throw new RuntimeException("В файле «{$source}» некорректный timestamp в date_range.");
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Нормализует строку: trim и нижний регистр.
     */
    private function normalizeStr(string $value): string
    {
        return strtolower(trim($value));
    }
}
