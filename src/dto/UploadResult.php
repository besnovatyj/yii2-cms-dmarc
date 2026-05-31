<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\dto;

/**
 * Результат загрузки и импорта DMARC-архивов.
 */
final class UploadResult
{
    /** @var string[] */
    public array $errors = [];

    /** @var string[] */
    public array $skipped = [];

    public function __construct(
        public int $imported = 0,
    ) {
    }

    /**
     * Добавляет сообщение об ошибке.
     */
    public function addError(string $filename, string $message): void
    {
        $this->errors[] = "[{$filename}] {$message}";
    }

    /**
     * Помечает файл как пропущенный (дубликат).
     */
    public function addSkipped(string $reportId): void
    {
        $this->skipped[] = $reportId;
    }

    /**
     * Есть ли ошибки.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Есть ли пропущенные дубликаты.
     */
    public function hasSkipped(): bool
    {
        return $this->skipped !== [];
    }
}
