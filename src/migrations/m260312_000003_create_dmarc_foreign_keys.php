<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\migrations;

use common\components\migration\BaseMigration;
use yii\base\NotSupportedException;

/**
 * Добавляет внешние ключи между таблицами DMARC-модуля.
 */
class m260312_000003_create_dmarc_foreign_keys extends BaseMigration
{
    private const string FK_RECORDS_REPORT = 'fk-dmarc_report_records-dmarc_report_id';

    public function safeUp(): void
    {
        parent::safeUp();

        $this->addForeignKey(
            self::FK_RECORDS_REPORT,
            m260312_000002_create_dmarc_report_records_table::TABLE_NAME,
            'dmarc_report_id',
            m260312_000001_create_dmarc_reports_table::TABLE_NAME,
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {

    }
}
