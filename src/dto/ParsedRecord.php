<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\dto;

/**
 * Промежуточная структура с данными одной строки DMARC-отчёта.
 */
final class ParsedRecord
{
    /**
     * @param array<int, array{domain: string, selector: string|null, result: string}> $dkimExtra
     */
    public function __construct(
        public readonly string  $sourceIp,
        public readonly int     $messageCount,
        public readonly string  $disposition,
        public readonly string  $dkimResult,
        public readonly string  $spfResult,
        public readonly string  $headerFrom,
        public readonly ?string $envelopeFrom,
        public readonly ?string $authDkimDomain,
        public readonly ?string $authDkimSelector,
        public readonly ?string $authDkimResult,
        public readonly array   $dkimExtra,
        public readonly ?string $authSpfDomain,
        public readonly ?string $authSpfResult,
    ) {
    }
}
