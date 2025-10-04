<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\helpers\ReportTemplateHelper;
use croacworks\essentials\models\ReportTemplate;
use croacworks\essentials\models\ReportTemplateSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ReportTemplateController implements the CRUD actions for ReportTemplate model.
 */
class ReportTemplateController extends AuthorizationController
{

    /**
     * Lists all ReportTemplate models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ReportTemplate();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ReportTemplate model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionPreview($id)
    {
        $model = $this->findModel($id);

        // 🔹 Dados de exemplo (mock) — usados apenas na prévia
        $sampleData = [
            // Dados simples
            'patient_name' => 'John Doe',
            'date' => date('d/m/Y'),
            'date_start' => '01/10/2025',
            'date_end' => '04/10/2025',
            'total' => '$1,250.00',

            // Dados de lista
            'items' => [
                [
                    'service_name' => 'Consultation',
                    'value' => '$200.00',
                    'date' => '01/10/2025',
                ],
                [
                    'service_name' => 'X-Ray',
                    'value' => '$400.00',
                    'date' => '02/10/2025',
                ],
                [
                    'service_name' => 'Ultrasound',
                    'value' => '$650.00',
                    'date' => '03/10/2025',
                ],
            ],
        ];

        $rendered = ReportTemplateHelper::render($model->body_html, $sampleData);

        // 🔹 Renderiza header/footer, se existirem
        $header = $model->header_html ? "<div style='border-bottom:1px solid #ccc;padding:8px 0;margin-bottom:15px'>{$model->header_html}</div>" : '';
        $footer = $model->footer_html ? "<hr><div style='font-size:12px;color:#666;margin-top:15px'>{$model->footer_html}</div>" : '';

        // 🔹 Exibe visualização simples e centralizada
        return $this->renderContent("
        <div style='max-width:900px;margin:40px auto;font-family:Arial, sans-serif;'>
            {$header}
            <div>{$rendered}</div>
            {$footer}
        </div>
    ");
    }

    /**
     * Creates a new ReportTemplate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new ReportTemplate();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing ReportTemplate model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing ReportTemplate model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
}
