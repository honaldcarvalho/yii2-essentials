<?php

namespace croacworks\essentials\controllers;

use Yii;

use yii\web\NotFoundHttpException;
use yii\db\Query;
use yii\web\Response;
use croacworks\essentials\models\File;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\controllers\AuthorizationController as Authz;
/**
 * FileController implements the CRUD actions for File model.
 */
class FileController extends AuthorizationController
{

    public function beforeAction($action)
    {
        if (in_array($action->id, ['delete', 'delete-files'], true)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        return parent::beforeAction($action);
    }
    /**
     * Lists all File models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new File();
        $searchModel->scenario = File::SCENARIO_SEARCH;
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single File model.
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

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionMove()
    {
        $moved = '';
        $noMoved = '';

        if (Yii::$app->request->isPost) {

            $files_id = Yii::$app->request->post()['file_selected'] ?? [];
            $folder_id = Yii::$app->request->post()['folder_id'] ?? null;
            if ($folder_id !== null && !empty($files_id)) {
                foreach ($files_id as $file_id) {
                    try {

                        if ($this::isMaster()) {
                            $model = File::find()->where(['id' => $file_id])->one();
                        } else {
                            $model = $model = File::find()->where(['id' => $file_id])->andWhere(['or', ['in', 'group_id', $this::getUserGroups()]])->one();
                        }

                        $model->folder_id = $folder_id;
                        if ($model->save()) {
                            $moved .= "({$model->name}) ";
                        } else {
                            $noMoved .= "({$model->name}) ";
                        }
                    } catch (\Throwable $th) {
                        $noMoved .= "(File #{$file_id}) ";
                    }
                }
                if (!empty($moved))
                    Yii::$app->session->setFlash("success", Yii::t('app', 'Files moved: ') . $moved);
                if (!empty($noMoved))
                    Yii::$app->session->setFlash("danger", Yii::t('app', 'Files not moved')) . $noMoved;
            }
        }
        return $this->redirect(['file/index']);
    }

    public function actionRemoveFile($id)
    {
        $folder_id = Yii::$app->request->get('folder');
        if ($this::isMaster()) {
            $model = File::find()->where(['id' => $id])->one();
        } else {
            $model = File::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $this::getUserGroups()]])->one();
        }
        try {
            $model->folder_id = null;
            $model->save();
            Yii::$app->session->setFlash("success", Yii::t('app', 'File removed'));
        } catch (\Throwable $th) {
            Yii::$app->session->setFlash("error", Yii::t('app', 'File not removed'));
        }

        return $this->redirect(['folder/view', 'id' => $folder_id]);
    }
    /**
     * Deletes an existing File model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return array
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function remove($id)
    {
        $thumb = false;
        $file = false;

        $model = File::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $this::getUserGroups()]])->one();
        $folder_id = $model->folder_id;

        if ($model->delete()) {
            $file = @unlink($model->path);

            if ($model->pathThumb) {
                $thumb = @unlink($model->pathThumb);
            }
        }

        return [
            'file' => $file,
            'thumb' => $thumb,
            'folder_id' => $folder_id,
        ];
    }

    /** Check if a file is referenced anywhere (FKs + heuristic file_id). */
    protected function canDeleteFile(File $file): array
    {
        $db = Yii::$app->db;
        $schema = $db->schema;
        $tables = $schema->getTableSchemas();
        $fileTable = File::tableName();
        $fileId = (int)$file->id;
        $refs = [];

        // 1) real FKs pointing to files(id)
        foreach ($tables as $tbl) {
            foreach ($tbl->foreignKeys as $fk) {
                $refTable = $fk[0] ?? null;
                if ($refTable === $fileTable) {
                    foreach ($fk as $local => $ref) {
                        if ($local === 0) continue;
                        if ($ref === 'id') {
                            $count = (new Query())
                                ->from($tbl->name)
                                ->where([$local => $fileId])
                                ->limit(1)->count('*', $db);
                            if ($count > 0) {
                                $refs[] = ['table' => $tbl->name, 'column' => $local, 'count' => (int)$count];
                            }
                        }
                    }
                }
            }
        }

        // 2) heuristic: plain file_id columns
        foreach ($tables as $tbl) {
            if ($tbl->getColumn('file_id') !== null) {
                $count = (new Query())
                    ->from($tbl->name)
                    ->where(['file_id' => $fileId])
                    ->limit(1)->count('*', $db);
                if ($count > 0 && !array_filter($refs, fn($r) => $r['table'] === $tbl->name && $r['column'] === 'file_id')) {
                    $refs[] = ['table' => $tbl->name, 'column' => 'file_id', 'count' => (int)$count];
                }
            }
        }

        return ['allowed' => empty($refs), 'refs' => $refs];
    }

    /** DELETE single (JSON) */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;

        if (!$req->isPost) {
            Yii::$app->response->statusCode = 405; // Method Not Allowed
            return ['success' => false, 'error' => 'Method Not Allowed'];
        }

        try {
            $model = $this->findModel($id);
            if (!$model) {
                Yii::$app->response->statusCode = 404;
                return ['success' => false, 'error' => 'Not found', 'id' => (int)$id];
            }

            // Pré-checagem global (FK reais + heurística file_id)
            $check = Authz::canDeleteModel($model, ['file_id']);
            if (!($check['allowed'] ?? false)) {
                Yii::$app->response->statusCode = 409; // Conflict
                return [
                    'success' => false,
                    'blocked' => true,
                    'id'      => (int)$model->id,
                    'refs'    => $check['refs'] ?? [],
                    'message' => 'File is referenced and cannot be removed.',
                ];
            }

            // Mantém sua rotina atual de remoção (disco + DB)
            $res = StorageController::removeFile($model->id, [
                'force' => \croacworks\essentials\controllers\AuthorizationController::isMaster(),
                // 'ignoreMissing' => true,   // opcional
                // 'deleteThumb'   => true,   // opcional
            ]);
            $ok  = (bool)($res['success'] ?? false);

            if (!$ok) {
                Yii::$app->response->statusCode = 500;
            }

            return [
                'success' => $ok,
                'id'      => (int)$model->id,
                'result'  => $res,
            ];
        } catch (\yii\db\IntegrityException $e) {
            // Se o BD bloquear, re-escaneia com o helper global para informar onde está referenciado
            $refs = Authz::findReferences($model::tableName(), 'id', $model->id, ['file_id']);
            Yii::$app->response->statusCode = 409; // Conflict
            return [
                'success' => false,
                'blocked' => true,
                'id'      => (int)$model->id,
                'refs'    => $refs,
                'message' => 'File is referenced and cannot be removed.',
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'error' => $e->getMessage(), 'id' => (int)$id];
        }
    }


    /** BULK delete (JSON) - expects file_selected[] in POST */
    public function actionDeleteFiles()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'error' => 'Bad Request'];
        }

        $ids = (array)Yii::$app->request->post('file_selected', []);
        if (!$ids) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'error' => 'No files selected'];
        }

        $deleted = [];
        $blocked = []; // has refs
        $failed  = []; // unexpected error or access denied

        foreach ($ids as $rawId) {
            $id = (int)$rawId;

            try {
                $model = $this->findModel($id);
                if (!$model) {
                    $failed[] = ['id' => $id, 'error' => 'not_found'];
                    continue;
                }

                // Pré-checagem global (FK reais + heurística file_id)
                $check = Authz::canDeleteModel($model, ['file_id']);
                if (!($check['allowed'] ?? false)) {
                    $blocked[] = ['id' => $id, 'refs' => $check['refs'] ?? []];
                    continue;
                }

                // Remove (disco + DB) via sua rotina atual
                $res = StorageController::removeFile($model->id);
                if (!empty($res['success'])) {
                    $deleted[] = $id;
                } else {
                    $failed[] = ['id' => $id, 'error' => $res['message'] ?? 'unknown'];
                }
            } catch (\yii\db\IntegrityException $e) {
                // Caso algum passe na pré-checada (raro) e ainda assim falhe no BD
                $refs = Authz::findReferences($model::tableName(), 'id', $model->id, ['file_id']);
                $blocked[] = ['id' => $id, 'refs' => $refs];
            } catch (\Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                $failed[] = ['id' => $id, 'error' => 'exception'];
            }
        }

        return [
            'success' => true,
            'summary' => [
                'deleted' => count($deleted),
                'blocked' => count($blocked),
                'failed'  => count($failed),
            ],
            'deleted_ids' => $deleted,
            'blocked'     => $blocked,
            'failed'      => $failed,
        ];
    }


    /**
     * Ex.: GET /f/<slug>
     */
    public function actionOpen(string $slug)
    {
        /** @var File|null $file */
        $file = File::find()->where(['slug' => $slug])->one();
        if (!$file) {
            throw new NotFoundHttpException('Link inválido ou arquivo não encontrado.');
        }

        $abs = Yii::getAlias('@webroot') . $file->path;

        if (!$abs || !is_file($abs)) {
            // Loga para facilitar debug em prod
            Yii::error([
                'msg'       => 'Arquivo indisponível',
                'file_id'   => $file->id ?? null,
                'path'      => $file->path ?? null,
                'webroot'   => Yii::getAlias('@webroot'),
                'candidates' => $this->lastPathCandidates ?? [],
            ], __METHOD__);

            throw new NotFoundHttpException('Arquivo indisponível.');
        }

        // Se expirou, ainda serve nesta request e já renova slug +1 dia p/ próximos acessos
        $now     = time();
        $expired = !empty($file->expires_at) && (int)$file->expires_at < $now;
        if ($expired) {
            $this->rotateSlugAndRenew($file);
        }

        // Descobre mime (ou derive de extensão, se preferir)
        $mime = @mime_content_type($abs) ?: ($file->mime ?? 'application/octet-stream');

        $res = Yii::$app->response;
        $res->format = \yii\web\Response::FORMAT_RAW;
        $res->headers->set('Content-Type', $mime);
        $res->headers->set('Content-Disposition', 'inline; filename="' . basename($abs) . '"');
        $res->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $res->headers->set('Pragma', 'no-cache');

        // Caso use mod_xsendfile, troque as 2 linhas abaixo pelo header X-Sendfile
        return file_get_contents($abs);
    }

    /** Gera novo slug e renova expiração (+1 dia) */
    protected function rotateSlugAndRenew(File $file): void
    {
        $newSlug = $this->generateUniqueSlug(32);
        $newExp  = time() + 3600; // +1 hora

        try {
            $file->updateAttributes([
                'slug'       => $newSlug,
                'expires_at' => $newExp,
            ]);
        } catch (\Throwable $e) {
            Yii::error(['slug_rotate_fail' => $e->getMessage(), 'file_id' => $file->id], __METHOD__);
        }
    }

    /** Slug único checando colisão */
    protected function generateUniqueSlug(int $len = 32): string
    {
        do {
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $n = strlen($alphabet);
            $s = '';
            for ($i = 0; $i < $len; $i++) {
                $s .= $alphabet[random_int(0, $n - 1)];
            }
            $candidate = $s;
        } while (File::find()->where(['slug' => $candidate])->exists());
        return $candidate;
    }

    /** ---------- RESOLUÇÃO CORRETA DO CAMINHO FÍSICO ---------- */
    private array $lastPathCandidates = [];
}
