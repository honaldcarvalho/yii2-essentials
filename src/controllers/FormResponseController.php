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
        // Se veio POST/arquivos, trata como JSON (mesma ideia do update)
        if (Yii::$app->request->isPost || !empty($_FILES)) {
            return $this->actionCreateJson();
        }

        $model = new FormResponse();
        return $this->render('create', ['model' => $model]);
    }

    /**
     * Cria um FormResponse recebendo os dados do DynamicModel (e arquivos) via JSON/AJAX.
     * Espera receber o dynamic_form_id. Pode vir em:
     * - POST['dynamic_form_id']
     * - POST['FormResponse']['dynamic_form_id']
     * - GET['dynamic_form_id']
     */
    public function actionCreateJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $req = Yii::$app->request;

        $dynamicFormId =
            (int)$req->post('dynamic_form_id', 0)
            ?: (int)($req->post('FormResponse')['dynamic_form_id'] ?? 0)
            ?: (int)$req->get('dynamic_form_id', 0);

        if ($dynamicFormId <= 0) {
            return ['success' => false, 'error' => 'Missing dynamic_form_id'];
        }

        // Campos do DynamicForm
        $fields = FormField::find()
            ->where(['dynamic_form_id' => $dynamicFormId])
            ->indexBy('name')
            ->all();

        // Dados vindos do formulário (sem arquivos)
        $postData = $req->post('DynamicModel', []);

        $data = [];

        foreach ($fields as $name => $field) {
            $type = (int)$field->type;

            // Arquivo via StorageController
            if ($type === FormFieldType::TYPE_FILE) {
                // nome do input file deve ser DynamicModel[<name>]
                $uploaded = UploadedFile::getInstanceByName("DynamicModel[$name]");

                // sem upload => null
                if (!$uploaded) {
                    $data[$name] = null;
                    continue;
                }

                // (opcional) validações específicas por campo
                if ($name === 'matrix') {
                    $isPdf = in_array($uploaded->type, ['application/pdf'], true) || preg_match('/\.pdf$/i', $uploaded->name);
                    if (!$isPdf) return ['success' => false, 'error' => 'Matrix must be a PDF'];
                }

                $fileId = $this->storeWithStorage($uploaded, $field->label ?? $uploaded->name);
                if (!$fileId) {
                    return ['success' => false, 'error' => 'Upload failed'];
                }

                $data[$name] = (string)$fileId;
                continue;
            }

            // Tipos não-arquivo
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

        // Cria o registro
        $model = new FormResponse();
        $model->dynamic_form_id = $dynamicFormId;
        $model->response_data   = $data;

        // Preenche group_id automaticamente se existir no AR
        if ($model->hasAttribute('group_id')) {
            $model->group_id = (int)(Yii::$app->user->identity->group_id ?? $model->group_id ?? 1);
        }

        if ($model->save(false)) {
            return [
                'success'  => true,
                'message'  => 'Data created',
                'id'       => (int)$model->id,
                'redirect' => $this->createUrl(['view', 'id' => $model->id]),
            ];
        }

        return ['success' => false, 'error' => 'Save failed'];
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
            if ($type === FormFieldType::TYPE_FILE) {
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
