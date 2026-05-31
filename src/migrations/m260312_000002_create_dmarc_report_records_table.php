<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\migrations;

use common\components\migration\BaseMigration;
use yii\base\NotSupportedException;

/**
 * Создаёт таблицу записей DMARC-отчётов (IP-строки).
 */
class m260312_000002_create_dmarc_report_records_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%dmarc_report_records}}';

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
            'id'               => $this->primaryKey()->unsigned()->comment('Идентификатор записи'),
            'dmarc_report_id'  => $this->integer()->unsigned()->notNull()->comment('ID отчёта (FK)'),
            'source_ip'        => $this->string(45)->notNull()->comment('IP-адрес отправителя (IPv4 или IPv6)'),
            'message_count'    => $this->integer()->unsigned()->notNull()->defaultValue(1)->comment('Количество писем с этого IP'),
            'disposition'      => $this->string(20)->notNull()->defaultValue('none')->comment('Итоговое действие: none, quarantine, reject'),
            'dkim_result'      => $this->string(10)->notNull()->defaultValue('fail')->comment('Результат DKIM по политике: pass, fail'),
            'spf_result'       => $this->string(10)->notNull()->defaultValue('fail')->comment('Результат SPF по политике: pass, fail'),
            'header_from'      => $this->string(255)->notNull()->comment('Домен из заголовка From'),
            'envelope_from'    => $this->string(255)->null()->comment('Домен из envelope MAIL FROM'),
            'auth_dkim_domain' => $this->string(255)->null()->comment('Домен DKIM-подписи'),
            'auth_dkim_selector' => $this->string(255)->null()->comment('Селектор DKIM-подписи'),
            'auth_dkim_result' => $this->string(20)->null()->comment('Результат DKIM-аутентификации'),
            'auth_dkim_extra'  => $this->text()->null()->comment('Дополнительные DKIM-результаты (JSON)'),
            'auth_spf_domain'  => $this->string(255)->null()->comment('Домен SPF-проверки'),
            'auth_spf_result'  => $this->string(20)->null()->comment('Результат SPF-аутентификации'),
            'created_at'       => $this->dateTime()->notNull()->defaultExpression('NOW()')->comment('Дата создания'),
        ], $this->tableOptions);

        $this->addCommentOnTable(static::TABLE_NAME, 'Записи DMARC-отчётов (IP-строки)');

        $this->createIndexes(static::TABLE_NAME, 'dmarc_report_id');
        $this->createIndexes(static::TABLE_NAME, 'source_ip');
        $this->createIndexes(static::TABLE_NAME, 'disposition');
        $this->createIndexes(static::TABLE_NAME, 'dkim_result');
        $this->createIndexes(static::TABLE_NAME, 'spf_result');
    }

    /**
     * @throws NotSupportedException
     */
    public function safeDown(): void
    {
        parent::safeDown();
    }
}
