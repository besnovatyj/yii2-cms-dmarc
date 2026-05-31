<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\forms\backend\search;

use Besnovatyj\Dmarc\entities\DmarcReport;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Поисковая форма для грида DMARC-отчётов.
 */
class ReportSearch extends Model
{
    /** @var string|null Фильтр по названию организации */
    public ?string $org_name = null;

    /** @var string|null Фильтр по домену */
    public ?string $domain = null;

    /** @var string|null Начало периода фильтрации (дата) */
    public ?string $date_from = null;

    /** @var string|null Конец периода фильтрации (дата) */
    public ?string $date_to = null;

    /**
     * Показывать только отчёты с ошибками аутентификации (DKIM или SPF fail).
     */
    public bool $only_failures = false;

    /**
     * Показывать только отчёты с заблокированными письмами (quarantine/reject).
     */
    public bool $only_blocked = false;

    public function rules(): array
    {
        return [
            [['org_name', 'domain', 'date_from', 'date_to'], 'string', 'max' => 255],
            [['org_name', 'domain', 'date_from', 'date_to'], 'trim'],
            [['org_name', 'domain', 'date_from', 'date_to'], 'default', 'value' => null],
            [['only_failures', 'only_blocked'], 'boolean'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'org_name'      => 'Организация',
            'domain'        => 'Домен',
            'date_from'     => 'Период с',
            'date_to'       => 'Период по',
            'only_failures' => 'Только с ошибками',
            'only_blocked'  => 'Только с блокировкой',
        ];
    }

    /**
     * Строит DataProvider для GridView.
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = DmarcReport::find()->latestFirst();

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 25],
            'sort'       => [
                'defaultOrder' => ['date_begin' => SORT_DESC],
                'attributes'   => ['date_begin', 'org_name', 'domain', 'policy_p'],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->org_name !== null && $this->org_name !== '') {
            $query->byOrgName($this->org_name);
        }

        if ($this->domain !== null && $this->domain !== '') {
            $query->byDomain($this->domain);
        }

        if ($this->date_from !== null && $this->date_from !== '') {
            $query->dateBeginFrom($this->date_from . ' 00:00:00');
        }

        if ($this->date_to !== null && $this->date_to !== '') {
            $query->dateEndTo($this->date_to . ' 23:59:59');
        }

        if ($this->only_failures) {
            $query->withFailures();
        }

        if ($this->only_blocked) {
            $query->withBlocked();
        }

        return $dataProvider;
    }

    /**
     * Список уникальных организаций для выпадающего фильтра.
     *
     * @return array<string, string>
     */
    public function orgNamesList(): array
    {
        $rows = DmarcReport::find()
            ->select('org_name')
            ->distinct()
            ->orderBy(['org_name' => SORT_ASC])
            ->column();

        return array_combine($rows, $rows);
    }

    /**
     * Список уникальных доменов для выпадающего фильтра.
     *
     * @return array<string, string>
     */
    public function domainsList(): array
    {
        $rows = DmarcReport::find()
            ->select('domain')
            ->distinct()
            ->orderBy(['domain' => SORT_ASC])
            ->column();

        return array_combine($rows, $rows);
    }
}
