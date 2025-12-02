<?php

namespace croacworks\essentials\controllers;

use Yii;

use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\Url;
use croacworks\essentials\enums\FormFieldType;
use croacworks\essentials\models\FormResponse;
use croacworks\essentials\models\FormField;
use croacworks\essentials\controllers\rest\StorageController;

class FormResponseController extends AuthorizationController
{
    /**
     * Standard list view.
     */
    public function actionIndex()
    {
        $searchModel = new FormResponse();
        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams,
            ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC]]
        );

        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    /**
     * Standard detail view.
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Returns the widget form via AJAX.
     */
    public function actionEdit($id)
    {
        $model = $this->findModel($id);
        return $this->renderAjax('_form_widget', ['model' => $model]);
    }

    /**
     * Creates a new response via JSON/AJAX.
     */
    public function actionCreateJson(bool $asJson = true)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $req = Yii::$app->request;
            $dynamicFormId =
                (int)$req->post('dynamic_form_id', 0)
                ?: (int)($req->post('FormResponse')['dynamic_form_id'] ?? 0);

            if ($dynamicFormId <= 0) {
                return ['success' => false, 'error' => Yii::t('app', 'Missing dynamic_form_id')];
            }

            // Create new instance
            $model = new FormResponse(['dynamic_form_id' => $dynamicFormId]);

            return $this->processAndSave($model);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Updates an existing response via JSON/AJAX.
     */
    public function actionUpdateJson($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $model = $this->findModel($id);
            return $this->processAndSave($model);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ====================================================
    // ============== PROTECTED PROCESSOR =================
    // ====================================================

    /**
     * Centralized logic to process fields, uploads and save logic.
     * Keeps actions skinny.
     */
    protected function processAndSave(FormResponse $model): array
    {
        $req = Yii::$app->request;
        $postData = $req->post('DynamicModel', []);

        // Find field definitions
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $model->dynamic_form_id])
            ->indexBy('name')
            ->all();

        if (empty($fields)) {
            return ['success' => false, 'error' => Yii::t('app', 'No fields found for this form')];
        }

        // Process inputs
        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            // Handle File Uploads
            if ($this->isFileType($type)) {
                $this->handleFileUpload($model, $name, $field->label, $postData);
                continue;
            }

            // Handle Standard Inputs
            $value = $postData[$name] ?? null;

            // Use Magic Method __set on model
            $model->$name = $this->formatValue($type, $value);
        }

        if ($model->save(false)) {
            return [
                'success'  => true,
                'message'  => $model->isNewRecord ? Yii::t('app', 'Data created') : Yii::t('app', 'Data updated'),
                'id'       => (int)$model->id,
                'redirect' => Url::to(['view', 'id' => (int)$model->id]),
            ];
        }

        return ['success' => false, 'error' => Yii::t('app', 'Save failed')];
    }

    // ====================================================
    // ================= HELPER METHODS ===================
    // ====================================================

    /**
     * Formats the value based on field type.
     */
    protected function formatValue(int $type, $value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        switch ($type) {
            case FormFieldType::TYPE_NUMBER:
                return is_numeric($value) ? $value + 0 : null;

            case FormFieldType::TYPE_CHECKBOX:
            case FormFieldType::TYPE_MULTIPLE:
                return (array)$value;

            case FormFieldType::TYPE_DATE:
            case FormFieldType::TYPE_DATETIME:
                $ts = strtotime((string)$value);
                return $ts ? date('Y-m-d H:i:s', $ts) : null;

            default:
                return $value;
        }
    }

    protected function isFileType(int $type): bool
    {
        return $type === FormFieldType::TYPE_FILE || $type === FormFieldType::TYPE_PICTURE;
    }

    /**
     * Handles upload, deletion of old files and setting model attributes.
     */
    protected function handleFileUpload(FormResponse $model, string $fieldName, ?string $label, array $postData): void
    {
        $uploaded = UploadedFile::getInstanceByName("DynamicModel[$fieldName]");
        $wantsClear = (string)($postData[$fieldName . '_clear'] ?? '0') === '1';

        // Use Magic Getter to find existing ID
        $oldId = (int)($model->$fieldName ?? 0);

        // User requested to clear the file
        if ($wantsClear && !$uploaded) {
            if ($oldId > 0) {
                $this->deleteFileId($oldId);
            }
            $model->$fieldName = null;
            return;
        }

        // No new file and no clear request
        if (!$uploaded) {
            return;
        }

        // Process new upload
        $fileId = $this->storeWithStorage($uploaded, $label ?? $uploaded->name);

        if ($fileId) {
            // Remove old file if it existed
            if ($oldId > 0 && $fileId !== $oldId) {
                $this->deleteFileId($oldId);
            }
            $model->$fieldName = (string)$fileId;
        }
    }

    protected function storeWithStorage(UploadedFile $uploaded, string $description): ?int
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

    protected function deleteFileId(int $fileId, array $opts = []): bool
    {
        if ($fileId <= 0) return true;

        $opts = array_merge([
            'force'         => false,
            'ignoreMissing' => true,
            'deleteThumb'   => true,
        ], $opts);

        try {
            $res = StorageController::removeFile($fileId, $opts);
            return is_array($res) && ($res['success'] ?? false) === true;
        } catch (\Throwable $e) {
            Yii::error("removeFile exception for #$fileId: " . $e->getMessage(), __METHOD__);
        }
        return false;
    }
}
