<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\controllers\backend;

use Besnovatyj\Dmarc\forms\backend\UploadForm;
use Besnovatyj\Dmarc\services\manage\DmarcManageService;
use Throwable;
use Yii;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Контроллер загрузки DMARC-архивов.
 */
class UploadController extends Controller
{
    public function __construct(
        string                         $id,
        \yii\base\Module               $module,
        private readonly DmarcManageService $service,
        array                          $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Страница загрузки файлов.
     */
    public function actionIndex(): Response|string
    {
        $form = new UploadForm();

        if (Yii::$app->request->isPost) {
            $form->load(Yii::$app->request->post());
            $form->loadFiles();

            if ($form->validate()) {
                try {
                    $result = $this->service->uploadFiles($form->files);

                    if ($result->imported > 0) {
                        Yii::$app->session->setFlash(
                            $result->hasErrors() ? 'warning' : 'success',
                            "Импортировано отчётов: {$result->imported}."
                            . ($result->hasSkipped()
                                ? ' Пропущено дубликатов: ' . count($result->skipped) . '.'
                                : '')
                        );
                    } elseif ($result->hasSkipped()) {
                        Yii::$app->session->setFlash(
                            'info',
                            'Все файлы уже были импортированы ранее (дубликаты пропущены).'
                        );
                    }

                    if ($result->hasErrors()) {
                        Yii::$app->session->setFlash(
                            'error',
                            'Ошибки при импорте:<br>' . implode('<br>', array_map(
                                fn(string $e) => htmlspecialchars($e),
                                $result->errors
                            ))
                        );
                    }

                    return $this->redirect(['/Dmarc/backend/report/index']);
                } catch (Throwable $e) {
                    Yii::$app->errorHandler->logException($e);
                    Yii::$app->session->setFlash(
                        'error',
                        YII_DEBUG ? VarDumper::dumpAsString($e) : 'Внутренняя ошибка при импорте.'
                    );
                }
            }
        }

        return $this->render('index', ['model' => $form]);
    }
}
