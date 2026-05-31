<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\repositories;

use RuntimeException;

/**
 * Исключение: запись не найдена в репозитории.
 */
class NotFoundException extends RuntimeException
{
}
