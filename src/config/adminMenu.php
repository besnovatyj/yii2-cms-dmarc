<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

return [
    // Список отчётов
    [
        'label'     => 'Отчёты DMARC',
        'iconClass' => 'bi bi-list-ul me-1',
        'url'       => ['/Dmarc/backend/report/index'],
        'active'    => static function () {
            return str_contains(Yii::$app->request->url, '/Dmarc/backend/report');
        },
        '_meta' => [
            'placements' => [
                [
                    'location'      => 'left-sidebar',
                    'group'         => 'DMARC',
                    'groupIcon'     => 'bi bi-shield-check',
                    'priority'      => 100,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],

    // Загрузка архивов
    [
        'label'     => 'Загрузить отчёт',
        'iconClass' => 'bi bi-upload me-1',
        'url'       => ['/Dmarc/backend/upload/index'],
        'active'    => static function () {
            return str_contains(Yii::$app->request->url, '/Dmarc/backend/upload');
        },
        '_meta' => [
            'placements' => [
                [
                    'location'      => 'left-sidebar',
                    'group'         => 'DMARC',
                    'groupIcon'     => 'bi bi-shield-check',
                    'priority'      => 200,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],
];
