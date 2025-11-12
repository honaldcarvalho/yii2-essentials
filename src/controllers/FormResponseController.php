<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\Url;
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
        if (property_exists($this, 'formDef') && $this->formDef) {
            $model->dynamic_form_id = (int)$this->formDef->id;
        }

        if (Yii::$app->request->isPost || !empty($_FILES)) {
            $res = $this->createJson([
                'req'        => Yii::$app->request,
                'storeFile'  => fn(UploadedFile $u, string $d) => $this->storeWithStorage($u, $d),
                'deleteFile' => fn(int $id) => $this->deleteFileId($id),
                'decode'     => fn($raw) => $this->decodeResponseData($raw),
                'formDefId'  => (property_exists($this, 'formDef') && $this->formDef) ? (int)$this->formDef->id : 0,
            ]);

            if (($res['success'] ?? false) === true) {
                Yii::$app->session->addFlash('success', Yii::t('app', 'Saved successfully.'));
                return $this->redirect(['view', 'id' => (int)$res['id']]);
            }

            Yii::$app->session->addFlash('error', $res['error'] ?? Yii::t('app', 'Save failed.'));
        }

        return $this->render('/form-response-crud/create', [
            'model'   => $model,
            'formDef' => $this->formDef ?? null,
        ]);
    }

    /** JSON endpoint: only receive request and return result. */
    public function actionCreateJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->createJson([
            'req'        => Yii::$app->request,
            'storeFile'  => fn(UploadedFile $u, string $d) => $this->storeWithStorage($u, $d),
            'deleteFile' => fn(int $id) => $this->deleteFileId($id),
            'decode'     => fn($raw) => $this->decodeResponseData($raw),
            'formDefId'  => (property_exists($this, 'formDef') && $this->formDef) ? (int)$this->formDef->id : 0,
        ]);
    }

    /** Core create handler. */
    public function createJson(array $opts): array
    {
        $req        = $opts['req'] ?? Yii::$app->request;
        $storeFile  = $opts['storeFile'] ?? null;
        $deleteFile = $opts['deleteFile'] ?? null; // reserved for symmetry
        $decode     = $opts['decode'] ?? null;
        $formDefId  = (int)($opts['formDefId'] ?? 0);

        if (!is_callable($storeFile))  return ['success' => false, 'error' => 'Missing file storage handler'];
        if (!is_callable($decode))     return ['success' => false, 'error' => 'Missing decoder'];

        // Resolve Dynamic Form ID (priority: formDefId > POST > GET)
        $dynamicFormId = $formDefId > 0 ? $formDefId : (
            (int)$req->post('dynamic_form_id', 0)
            ?: (int)($req->post('FormResponse')['dynamic_form_id'] ?? 0)
            ?: (int)$req->get('dynamic_form_id', 0)
        );

        if ($dynamicFormId <= 0) {
            return ['success' => false, 'error' => 'Missing dynamic_form_id'];
        }

        $fields = FormField::find()
            ->where(['dynamic_form_id' => $dynamicFormId])
            ->indexBy('name')
            ->all();

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

                if ($name === 'matrix') {
                    $isPdf = in_array($uploaded->type, ['application/pdf'], true) || preg_match('/\.pdf$/i', $uploaded->name);
                    if (!$isPdf) return ['success' => false, 'error' => 'Matrix must be a PDF'];
                }

                $fileId = $storeFile($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) return ['success' => false, 'error' => 'Upload failed'];

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
        $model->response_data   = $data;

        if ($model->hasAttribute('group_id')) {
            $model->group_id = (int)(Yii::$app->user->identity->group_id ?? $model->group_id ?? 1);
        }

        if ($model->save(false)) {
            return [
                'success'  => true,
                'message'  => Yii::t('app', 'Data created'),
                'id'       => (int)$model->id,
                'redirect' => Url::to(['view', 'id' => (int)$model->id]),
            ];
        }

        return ['success' => false, 'error' => Yii::t('app', 'Save failed')];
    }

    public function actionUpdate($id)
    {
        if (Yii::$app->request->isPost || !empty($_FILES)) {
            return $this->actionUpdateJson($id);
        }

        $model = $this->findModel($id);
        return $this->render('update', ['model' => $model]);
    }

    /** JSON endpoint: only receive request and return result. */
    public function actionUpdateJson($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->updateJson([
            'id'         => (int)$id,
            'req'        => Yii::$app->request,
            'storeFile'  => fn(UploadedFile $u, string $d) => $this->storeWithStorage($u, $d),
            'deleteFile' => fn(int $fid) => $this->deleteFileId($fid),
            'decode'     => fn($raw) => $this->decodeResponseData($raw),
        ]);
    }

    /** Core update handler. */
    public function updateJson(array $opts): array
    {
        $req        = $opts['req'] ?? Yii::$app->request;
        $id         = (int)($opts['id'] ?? 0);
        $storeFile  = $opts['storeFile'] ?? null;
        $deleteFile = $opts['deleteFile'] ?? null;
        $decode     = $opts['decode'] ?? null;

        if ($id <= 0)                  return ['success' => false, 'error' => 'Missing id'];
        if (!is_callable($storeFile))  return ['success' => false, 'error' => 'Missing file storage handler'];
        if (!is_callable($deleteFile)) return ['success' => false, 'error' => 'Missing file delete handler'];
        if (!is_callable($decode))     return ['success' => false, 'error' => 'Missing decoder'];

        $model = FormResponse::findOne($id);
        if (!$model) return ['success' => false, 'error' => 'FormResponse not found'];

        $postData = $req->post('DynamicModel', []);
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $model->dynamic_form_id])
            ->indexBy('name')
            ->all();

        $data = $decode($model->response_data);
        $existing = $data;

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            if ($type === FormFieldType::TYPE_FILE || $type === FormFieldType::TYPE_PICTURE) {
                $uploaded   = UploadedFile::getInstanceByName("DynamicModel[$name]");
                $wantsClear = (string)($postData[$name . '_clear'] ?? '0') === '1';
                $oldId      = (int)($existing[$name] ?? 0);

                if ($wantsClear && !$uploaded) {
                    if ($oldId > 0) $deleteFile($oldId);
                    $data[$name] = null;
                    continue;
                }

                if (!$uploaded) {
                    continue; // keep current value
                }

                if ($name === 'matrix') {
                    $isPdf = in_array($uploaded->type, ['application/pdf'], true) || preg_match('/\.pdf$/i', $uploaded->name);
                    if (!$isPdf) return ['success' => false, 'error' => 'Matrix must be a PDF'];
                }

                $fileId = $storeFile($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) return ['success' => false, 'error' => 'Upload failed'];

                if ($oldId > 0 && (int)$fileId !== $oldId) {
                    $deleteFile($oldId);
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
            return ['success' => true, 'message' => Yii::t('app', 'Data updated'), 'id' => (int)$model->id];
        }
        return ['success' => false, 'error' => Yii::t('app', 'Save failed')];
    }

    public function actionEdit($id)
    {
        $model = FormResponse::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('FormResponse not found.');
        }
        return $this->renderAjax('_form_widget', ['model' => $model]);
    }

    private function deleteFileId(int $fileId, array $opts = []): bool
    {
        if ($fileId <= 0) return true;

        $opts = array_merge([
            'force'         => false,
            'ignoreMissing' => true,
            'deleteThumb'   => true,
        ], $opts);

        try {
            $res = StorageController::removeFile($fileId, $opts);
            if (is_array($res) && ($res['success'] ?? false) === true) {
                return true;
            }
            Yii::warning('removeFile failed: ' . var_export($res, true), __METHOD__);
        } catch (\Throwable $e) {
            Yii::error("removeFile exception for #$fileId: " . $e->getMessage(), __METHOD__);
        }
        return false;
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
