<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\migrations;

use common\components\migration\BaseMigration;
use yii\base\NotSupportedException;

/**
 * Создаёт таблицу DMARC-отчётов.
 */
class m260312_000001_create_dmarc_reports_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%dmarc_reports}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id'                  => $this->primaryKey()->unsigned()->comment('Идентификатор'),
            'report_id'           => $this->string(255)->notNull()->comment('Уникальный ID отчёта из XML'),
            'org_name'            => $this->string(255)->notNull()->comment('Организация, выпустившая отчёт'),
            'contact_email'       => $this->string(255)->notNull()->comment('E-mail отправителя отчёта'),
            'extra_contact_info'  => $this->text()->null()->comment('Дополнительные контактные данные'),
            'date_begin'          => $this->dateTime()->notNull()->comment('Начало периода отчёта'),
            'date_end'            => $this->dateTime()->notNull()->comment('Конец периода отчёта'),
            'domain'              => $this->string(255)->notNull()->comment('Домен из policy_published'),
            'adkim'               => $this->char(1)->notNull()->defaultValue('r')->comment('Режим выравнивания DKIM: r=relaxed, s=strict'),
            'aspf'                => $this->char(1)->notNull()->defaultValue('r')->comment('Режим выравнивания SPF: r=relaxed, s=strict'),
            'policy_p'            => $this->string(20)->notNull()->defaultValue('none')->comment('Политика домена: none, quarantine, reject'),
            'policy_sp'           => $this->string(20)->null()->comment('Политика поддоменов: none, quarantine, reject'),
            'policy_pct'          => $this->tinyInteger()->unsigned()->notNull()->defaultValue(100)->comment('Процент писем, к которым применяется политика'),
            'source_filename'     => $this->string(500)->notNull()->comment('Имя исходного файла архива'),
            'created_at'          => $this->dateTime()->notNull()->defaultExpression('NOW()')->comment('Дата импорта'),
        ], $this->tableOptions);

        $this->addCommentOnTable(static::TABLE_NAME, 'DMARC aggregate-отчёты');

        $this->createIndexes(static::TABLE_NAME, 'report_id', false, true);
        $this->createIndexes(static::TABLE_NAME, 'domain');
        $this->createIndexes(static::TABLE_NAME, 'org_name');
        $this->createIndexes(static::TABLE_NAME, 'date_begin');
        $this->createIndexes(static::TABLE_NAME, 'date_end');
    }

    /**
     * @throws NotSupportedException
     */
    public function safeDown(): void
    {
        parent::safeDown();
    }
}
