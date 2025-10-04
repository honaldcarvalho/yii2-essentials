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

        // 🔹 Usa o helper para gerar e exibir o PDF direto no navegador
        return \croacworks\essentials\helpers\ReportTemplateHelper::generatePdf(
            $model->id,         // ID do template (busca no banco)
            $sampleData,        // Dados simulados
            'Report_Preview',   // Nome do arquivo
            'inline',           // Modo: 'inline' (abre no navegador) | 'download' (força download)
            [
                'margin_top'    => 50, // espaço maior para header
                'margin_bottom' => 40, // espaço maior para footer
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
