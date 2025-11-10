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

    public function actionCreate()
    {
        $model = new FormResponse();
        // se seu controller já tem $this->formDef, preserve:
        if (property_exists($this, 'formDef') && $this->formDef) {
            $model->dynamic_form_id = (int)$this->formDef->id;
        }

        if (Yii::$app->request->isPost || !empty($_FILES)) {
            // chama o handler sem forçar JSON
            $result = $this->actionCreateJson(false);
            if (is_array($result) && !empty($result['success'])) {
                Yii::$app->session->addFlash('success', Yii::t('app', 'Saved successfully.'));
                return $this->redirect(['view', 'id' => (int)$result['id']]);
            }
            // falha: exiba erro na UI
            Yii::$app->session->addFlash('error', $result['error'] ?? Yii::t('app', 'Save failed.'));
        }

        return $this->render('/form-response-crud/create', [
            'model'   => $model,
            'formDef' => $this->formDef ?? null,
        ]);
    }

    /**
     * Quando $asJson=true, responde em JSON (para AJAX).
     * Quando $asJson=false, apenas retorna o array com o resultado (uso interno).
     */
    public function actionCreateJson(bool $asJson = true)
    {
        $oldFormat = Yii::$app->response->format;
        if ($asJson && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }

        try {
            $req = Yii::$app->request;

            // Prioriza formDef se existir; senão, tenta POST/GET
            $dynamicFormId = 0;
            if (property_exists($this, 'formDef') && $this->formDef) {
                $dynamicFormId = (int)$this->formDef->id;
            }
            if ($dynamicFormId <= 0) {
                $dynamicFormId =
                    (int)$req->post('dynamic_form_id', 0)
                    ?: (int)($req->post('FormResponse')['dynamic_form_id'] ?? 0)
                    ?: (int)$req->get('dynamic_form_id', 0);
            }

            if ($dynamicFormId <= 0) {
                $out = ['success' => false, 'error' => 'Missing dynamic_form_id'];
                return $this->finishCreateJson($out, $asJson, $oldFormat);
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
                        $isPdf = in_array($uploaded->type, ['application/pdf'], true)
                            || preg_match('/\.pdf$/i', $uploaded->name);
                        if (!$isPdf) {
                            $out = ['success' => false, 'error' => 'Matrix must be a PDF'];
                            return $this->finishCreateJson($out, $asJson, $oldFormat);
                        }
                    }

                    $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                    if (!$fileId) {
                        $out = ['success' => false, 'error' => 'Upload failed'];
                        return $this->finishCreateJson($out, $asJson, $oldFormat);
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
            $model->response_data   = $data;

            if ($model->hasAttribute('group_id')) {
                $model->group_id = (int)(Yii::$app->user->identity->group_id ?? $model->group_id ?? 1);
            }

            if ($model->save(false)) {
                $out = [
                    'success'  => true,
                    'message'  => 'Data created',
                    'id'       => (int)$model->id,
                    'redirect' => Url::to(['view', 'id' => (int)$model->id]),
                ];
                return $this->finishCreateJson($out, $asJson, $oldFormat);
            }

            $out = ['success' => false, 'error' => 'Save failed'];
            return $this->finishCreateJson($out, $asJson, $oldFormat);

        } finally {
            // segurança extra: se por algum fluxo não passou pelo finish, restaure
            if (!$asJson) {
                Yii::$app->response->format = $oldFormat;
            }
        }
    }

    /** Helper: garante restauração do formato e saída consistente. */
    private function finishCreateJson(array $out, bool $asJson, $oldFormat)
    {
        if (!$asJson) {
            Yii::$app->response->format = $oldFormat;
            return $out;
        }
        // para chamadas AJAX, já está em JSON
        return $out;
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
        $existing = $data;

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            // FILE via StorageController
            if ($type === FormFieldType::TYPE_FILE || $type === FormFieldType::TYPE_PICTURE) {
                // nome do input file deve ser DynamicModel[<name>]
                $uploaded = UploadedFile::getInstanceByName("DynamicModel[$name]");
                $wantsClear = (string)($postData[$name . '_clear'] ?? '0') === '1';

                // ID antigo (se existir)
                $oldId = (int)($existing[$name] ?? 0);

                // 1) Remoção explícita (sem upload novo)
                if ($wantsClear && !$uploaded) {
                    if ($oldId > 0) {
                        $this->deleteFileId($oldId); // remove do storage
                    }
                    $data[$name] = null;
                    continue;
                }

                // 2) Sem upload e sem clear => mantém
                if (!$uploaded) {
                    // nada a fazer; preserva valor atual
                    continue;
                }

                // (opcional) validações específicas por campo
                if ($name === 'matrix') {
                    $isPdf = in_array($uploaded->type, ['application/pdf'], true) || preg_match('/\.pdf$/i', $uploaded->name);
                    if (!$isPdf) return ['success' => false, 'error' => 'Matrix must be a PDF'];
                }

                // 3) Faz upload do novo
                $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) {
                    return ['success' => false, 'error' => 'Upload failed'];
                }

                // 4) Se havia antigo e mudou, apaga o antigo
                if ($oldId > 0 && (int)$fileId !== $oldId) {
                    $this->deleteFileId($oldId);
                }

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
    
    private function deleteFileId(int $fileId, array $opts = []): bool
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
