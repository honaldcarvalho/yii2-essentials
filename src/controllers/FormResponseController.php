<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\FormField;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\controllers\rest\StorageController;

class FormResponseController extends AuthorizationController
{
    public function actionIndex()
    {
        $searchModel = new FormResponse();
        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams,
            ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC], 'order' => false]
        );

        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate()
    {
        $model = new FormResponse();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        // Blindagem: se veio post/arquivo, trate como JSON
        if (Yii::$app->request->isPost || !empty($_FILES)) {
            return $this->actionUpdateJson($id); // chama direto o handler certo
        }

        $model = $this->findModel($id);
        return $this->render('update', ['model' => $model]);
    }

    public function actionUpdateJson($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = FormResponse::findOne((int)$id);
        if (!$model) return ['success' => false, 'error' => 'FormResponse not found'];

        $postData = Yii::$app->request->post('DynamicModel', []);
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $model->dynamic_form_id])
            ->indexBy('name')
            ->all();

        $data = $this->decodeResponseData($model->response_data);

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            // FILE via StorageController
            if ($type === FormFieldType::TYPE_FILE) {
                $uploaded = UploadedFile::getInstanceByName("DynamicModel[$name]");

                if (!$uploaded) {
                    if ((string)($postData[$name . '_clear'] ?? '0') === '1') {
                        $data[$name] = null;
                    }
                    continue;
                }

                if ($name === 'matrix') {
                    $isPdf = in_array($uploaded->type, ['application/pdf'], true) || preg_match('/\.pdf$/i', $uploaded->name);
                    if (!$isPdf) return ['success' => false, 'error' => 'Matrix must be a PDF'];
                }

                $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) return ['success' => false, 'error' => 'Upload failed'];

                $data[$name] = (string)$fileId;
                continue;
            }

            // Valores vindos do POST para os demais tipos
            $value = $postData[$name] ?? null;

            if ($value === '') {
                $data[$name] = null;
                continue;
            }

            switch ($type) {
                case FormFieldType::TYPE_NUMBER:
                    $data[$name] = is_numeric($value) ? $value + 0 : null;
                    break;

                case FormFieldType::TYPE_CHECKBOX:
                case FormFieldType::TYPE_MULTIPLE:
                    $data[$name] = (array)$value;
                    break;

                case FormFieldType::TYPE_DATE:
                case FormFieldType::TYPE_DATETIME:
                    $ts = strtotime((string)$value);
                    $data[$name] = $ts ? date('Y-m-d H:i:s', $ts) : null;
                    break;

                case FormFieldType::TYPE_PHONE:
                case FormFieldType::TYPE_IDENTIFIER:
                case FormFieldType::TYPE_TEXT:
                case FormFieldType::TYPE_TEXTAREA:
                case FormFieldType::TYPE_SELECT:
                case FormFieldType::TYPE_SQL:
                case FormFieldType::TYPE_MODEL:
                case FormFieldType::TYPE_EMAIL:
                default:
                    $data[$name] = $value;
                    break;
            }
        }

        $model->response_data = $data;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Data updated'];
        }
        return ['success' => false, 'error' => 'Save failed'];
    }

    public function actionEdit($id)
    {
        $model = FormResponse::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('FormResponse not found.');
        }
        return $this->renderAjax('_form_widget', ['model' => $model]);
    }

    private function decodeResponseData($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $d = json_decode($raw, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    private function storeWithStorage(UploadedFile $uploaded, string $description): ?int
    {
        $groupId = (int)(Yii::$app->user->identity->group_id ?? 1);

        $res = StorageController::uploadFile($uploaded, [
            'file_name'     => null,
            'description'   => $description,
            'folder_id'     => 1,
            'group_id'      => $groupId,
            'attach_model'  => 0,
            'save'          => 1,
            'convert_video' => 0,
            'thumb_aspect'  => 1,
            'quality'       => 80,
        ]);

        if (!($res['success'] ?? false)) return null;

        $data = $res['data'] ?? null;
        $id = is_object($data) ? ($data->id ?? null) : ($data['id'] ?? null);
        return $id ? (int)$id : null;
    }
}