<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Dmarc\controllers\backend;

use Besnovatyj\Dmarc\entities\DmarcReport;
use Besnovatyj\Dmarc\forms\backend\search\ReportSearch;
use Besnovatyj\Dmarc\repositories\NotFoundException;
use Besnovatyj\Dmarc\services\manage\DmarcManageService;
use Throwable;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Контроллер просмотра и удаления DMARC-отчётов.
 */
class ReportController extends Controller
{
    public function __construct(
        string                         $id,
        \yii\base\Module               $module,
        private readonly DmarcManageService $service,
        array                          $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Список отчётов с фильтрацией.
     */
    public function actionIndex(): string
    {
        $searchModel  = new ReportSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel'  => $searchModel,
        ]);
    }

    /**
     * Детальный просмотр отчёта.
     *
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $report = $this->findModel($id);

        // Подгружаем записи сразу (eager loading)
        $report->populateRelation('records', $report->getRecords()->all());

        return $this->render('view', ['report' => $report]);
    }

    /**
     * Удаление отчёта.
     *
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id): Response
    {
        try {
            $this->service->remove($id);
            Yii::$app->session->setFlash('success', 'Отчёт удалён.');
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (Throwable $e) {
            Yii::$app->errorHandler->logException($e);
            Yii::$app->session->setFlash(
                'error',
                YII_DEBUG ? VarDumper::dumpAsString($e) : 'Ошибка удаления.'
            );
        }

        return $this->redirect(['index']);
    }

    /**
     * @throws NotFoundHttpException если отчёт не найден
     */
    private function findModel(int $id): DmarcReport
    {
        if (($report = DmarcReport::findOne($id)) !== null) {
            return $report;
        }

        throw new NotFoundHttpException('Отчёт не найден.');
    }
}
