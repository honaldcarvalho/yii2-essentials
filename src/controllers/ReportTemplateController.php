<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\helpers\ReportTemplateHelper;
use croacworks\essentials\models\ReportTemplate;
use croacworks\essentials\models\ReportTemplateSearch;
use Yii;
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
        // 🔹 O layout não é necessário em PDFs
        $this->layout = false;

        $model = $this->findModel($id);

        // 🔹 Mock data (exemplo)
        $sampleData = [
            'patient_name' => 'John Doe',
            'date'         => date('d/m/Y'),
            'date_start'   => '01/10/2025',
            'date_end'     => '04/10/2025',
            'total'        => '$1,250.00',
            'items' => [
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
                ['service_name' => 'Consultation', 'value' => '$200.00', 'date' => '01/10/2025'],
                ['service_name' => 'X-Ray',        'value' => '$400.00', 'date' => '02/10/2025'],
                ['service_name' => 'Ultrasound',   'value' => '$650.00', 'date' => '03/10/2025'],
            ],
        ];
        $fake_body_html = <<<HTML
        <h1 style="text-align:center; color:#287c36;">Financial Report</h1>
        <p><strong>Period:</strong> {date_start} - {date_end}</p>
        <table border="1" width="100%" cellspacing="0" cellpadding="6">
            <thead style="background:#f5f5f5;">
                <tr>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th style="text-align:right;">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr data-each="items">
                    <td>{patient_name}</td>
                    <td>{service_name}</td>
                    <td>{date}</td>
                    <td style="text-align:right;">{value}</td>
                </tr>
            </tbody>
        </table>
        <p style="text-align:right; margin-top:20px;"><strong>Total:</strong> {total}</p>
        HTML;
        $fakeData = Yii::$app->request->get('fakeData') ?? false;
        // 🔹 Usa o helper para gerar e exibir o PDF direto no navegador
        return \croacworks\essentials\helpers\ReportTemplateHelper::generatePdf(
            [
                'templateId' => $model->id,
                'data' => $fakeData ? $sampleData : '',
                'custom_body' => $fakeData ? $fake_body_html : null,
                'filename' => 'Report',
                'mode' => 'inline',
                'config' => [
                    'format'        => 'A4',
                    'margin_top'    => 40,
                    'margin_bottom' => 30,
                    'margin_left'   => 15,
                    'margin_right'  => 15,
                ],
                'normalizeHtml' => Yii::$app->request->get('normalize') ?? false,
                
            ]
        );
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

        if ($this->request->isPost && $model->load($this->request->post())){ 
            if ($model->save()){ 
                return $this->redirect(['view', 'id' => $model->id]);
            }
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
