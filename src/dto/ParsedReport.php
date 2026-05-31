<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\dto;

/**
 * Промежуточная структура с распарсенными данными DMARC-отчёта.
 */
final class ParsedReport
{
    /**
     * @param ParsedRecord[] $records
     */
    public function __construct(
        public readonly string  $reportId,
        public readonly string  $orgName,
        public readonly string  $contactEmail,
        public readonly ?string $extraContactInfo,
        public readonly string  $dateBegin,
        public readonly string  $dateEnd,
        public readonly string  $domain,
        public readonly string  $adkim,
        public readonly string  $aspf,
        public readonly string  $policyP,
        public readonly ?string $policySp,
        public readonly int     $policyPct,
        public readonly array   $records,
    ) {
    }
}
