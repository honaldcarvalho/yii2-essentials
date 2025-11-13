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
use yii\helpers\Url;

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

    public function actionCreateJson(bool $asJson = true)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $req = Yii::$app->request;
            $dynamicFormId =
                (int)$req->post('dynamic_form_id', 0)
                ?: (int)($req->post('FormResponse')['dynamic_form_id'] ?? 0)
                ?: (int)($this->formDef->id ?? 0);

            if ($dynamicFormId <= 0) {
                return ['success' => false, 'error' => 'Missing dynamic_form_id'];
            }

            return $this->createJson($dynamicFormId);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionUpdateJson($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $model = FormResponse::findOne((int)$id);
            if (!$model) {
                return ['success' => false, 'error' => 'FormResponse not found'];
            }

            return $this->updateJson($model);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ====================================================
    // ============== CORE GENERIC METHODS ================
    // ====================================================

    public function createJson(int $dynamicFormId): array
    {
        $req = Yii::$app->request;
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $dynamicFormId])
            ->indexBy('name')
            ->all();

        if (empty($fields)) {
            return ['success' => false, 'error' => 'No fields found'];
        }

        $postData = $req->post('DynamicModel', []);
        $data = [];

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            if ($type === FormFieldType::TYPE_FILE || $type === FormFieldType::TYPE_PICTURE) {
                $uploaded = UploadedFile::getInstanceByName("DynamicModel[$name]");

                if (!$uploaded) {
                    $data[$name] = null;
                    continue;
                }

                $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) {
                    return ['success' => false, 'error' => 'Upload failed'];
                }

                $data[$name] = (string)$fileId;
                continue;
            }

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
                default:
                    $data[$name] = $value;
                    break;
            }
        }

        $model = new FormResponse();
        $model->dynamic_form_id = $dynamicFormId;
        $model->response_data = $data;

        if ($model->hasAttribute('group_id')) {
            $model->group_id = (int)(Yii::$app->user->identity->group_id ?? 1);
        }

        if ($model->save(false)) {
            return [
                'success'  => true,
                'message'  => 'Data created',
                'id'       => (int)$model->id,
                'redirect' => Url::to(['view', 'id' => (int)$model->id]),
            ];
        }

        return ['success' => false, 'error' => 'Save failed'];
    }

    public function updateJson(FormResponse $model): array
    {
        $req = Yii::$app->request;
        $postData = $req->post('DynamicModel', []);
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $model->dynamic_form_id])
            ->indexBy('name')
            ->all();

        $data = $this->decodeResponseData($model->response_data);
        $existing = $data;

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            if ($type === FormFieldType::TYPE_FILE || $type === FormFieldType::TYPE_PICTURE) {
                $uploaded = UploadedFile::getInstanceByName("DynamicModel[$name]");
                $wantsClear = (string)($postData[$name . '_clear'] ?? '0') === '1';
                $oldId = (int)($existing[$name] ?? 0);

                if ($wantsClear && !$uploaded) {
                    if ($oldId > 0) {
                        $this->deleteFileId($oldId);
                    }
                    $data[$name] = null;
                    continue;
                }

                if (!$uploaded) {
                    continue;
                }

                $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) {
                    return ['success' => false, 'error' => 'Upload failed'];
                }

                if ($oldId > 0 && (int)$fileId !== $oldId) {
                    $this->deleteFileId($oldId);
                }

                $data[$name] = (string)$fileId;
                continue;
            }

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

    // ====================================================
    // ================= JSON SEARCH HELPER ===============
    // ====================================================

    /**
     * Busca um FormResponse por um campo dentro do JSON response_data.
     */
    public static function findByJsonField(string $field, $value, ?int $dynamicFormId = null): ?FormResponse
    {
        $query = FormResponse::find();

        if ($dynamicFormId) {
            $query->andWhere(['dynamic_form_id' => $dynamicFormId]);
        }

        $query->andWhere([
            '=',
            new \yii\db\Expression("JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.\"{$field}\"'))"),
            $value
        ]);

        return $query->one();
    }

    public function actionEdit($id)
    {
        $model = FormResponse::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('FormResponse not found.');
        }
        return $this->renderAjax('_form_widget', ['model' => $model]);
    }

    public function deleteFileId(int $fileId, array $opts = []): bool
    {
        if ($fileId <= 0) return true;

        // defaults seguros: respeita grupo, ignora físico ausente, remove thumb
        $opts = array_merge([
            'force'         => false,  // true só para master/batch
            'ignoreMissing' => true,
            'deleteThumb'   => true,
        ], $opts);

        try {
            $res = \croacworks\essentials\controllers\rest\StorageController::removeFile($fileId, $opts);
            if (is_array($res) && ($res['success'] ?? false) === true) {
                return true;
            }
            Yii::warning('removeFile failed: ' . var_export($res, true), __METHOD__);
        } catch (\Throwable $e) {
            Yii::error("removeFile exception for #$fileId: " . $e->getMessage(), __METHOD__);
        }
        return false;
    }

    public function decodeResponseData($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $d = json_decode($raw, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    public function storeWithStorage(UploadedFile $uploaded, string $description): ?int
    {
        $groupId = (int)(Yii::$app->user->identity->group_id ?? 1);

        $res = StorageController::uploadFile($uploaded, [
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
