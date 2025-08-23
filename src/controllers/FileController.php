<?php

namespace croacworks\essentials\controllers;

use Yii;

use yii\web\NotFoundHttpException;
use yii\db\Query;
use yii\web\Response;
use croacworks\essentials\models\File;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\rest\StorageController;

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

    public function actionMove()
    {   
        $moved = '';
        $noMoved = '';

        if(Yii::$app->request->isPost){

            $files_id = Yii::$app->request->post()['file_selected'] ?? [];
            $folder_id = Yii::$app->request->post()['folder_id'] ?? null;
            if($folder_id !== null && !empty($files_id)){
                foreach($files_id as $file_id){
                    try {
                        
                        if($this::isAdmin()){
                            $model = File::find()->where(['id'=>$file_id])->one();
                        }else{
                            $model = $model = File::find()->where(['id'=>$file_id])->andWhere(['or',['in','group_id',$this::getUserGroups()]])->one();
                        }
                        
                        $model->folder_id = $folder_id;
                        if($model->save()){
                            $moved .= "({$model->name}) ";
                        }else{
                            $noMoved .= "({$model->name}) ";
                        }

                    } catch (\Throwable $th) {
                        $noMoved .= "(File #{$file_id}) ";
                    }      
                }    
                if(!empty($moved))      
                    Yii::$app->session->setFlash("success", Yii::t('app', 'Files moved: ').$moved);
                if(!empty($noMoved))
                    Yii::$app->session->setFlash("danger", Yii::t('app', 'Files not moved')).$noMoved;
            }
        }
        return $this->redirect(['file/index']);
    }

    public function actionRemoveFile($id)
    {
        $folder_id = Yii::$app->request->get('folder');
        if($this::isAdmin()){
            $model = File::find()->where(['id'=>$id])->one();
        }else{
            $model = File::find()->where(['id'=>$id])->andWhere(['or',['in','group_id',$this::getUserGroups()]])->one();
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
        
        $model = File::find()->where(['id'=>$id])->andWhere(['or',['in','group_id',$this::getUserGroups()]])->one();
        $folder_id = $model->folder_id;

        if($model->delete()){
            $file = @unlink($model->path);

            if($model->pathThumb){
                $thumb = @unlink($model->pathThumb);
            }
        }

        return [
            'file'=>$file,
            'thumb'=>$thumb,
            'folder_id'=>$folder_id,
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
                                $refs[] = ['table'=>$tbl->name, 'column'=>$local, 'count'=>(int)$count];
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
                if ($count > 0 && !array_filter($refs, fn($r)=>$r['table']===$tbl->name && $r['column']==='file_id')) {
                    $refs[] = ['table'=>$tbl->name, 'column'=>'file_id', 'count'=>(int)$count];
                }
            }
        }

        return ['allowed'=>empty($refs), 'refs'=>$refs];
    }

    /** Group-aware loader (same logic from StorageController). */
    protected function findFileByAccess($id): ?File
    {
        $users_groups = AuthorizationController::getUserGroups();

        if (!AuthorizationController::isAdmin()) {
            return File::find()->where(['id'=>$id])
                ->andWhere(['or', ['in','group_id',$users_groups]])->one();
        }
        return File::find()->where(['id'=>$id])
            ->andWhere(['or', ['in','group_id',$users_groups], ['in','group_id',[null,1]]])->one();
    }

    /** DELETE single (JSON) */
    public function actionDelete($id)
    {
        if (!Yii::$app->request->isPost) {
            return ['success'=>false, 'error'=>'Bad Request'];
        }

        $model = $this->findFileByAccess($id);
        if (!$model) {
            return ['success'=>false, 'error'=>'Not found or access denied', 'id'=>(int)$id];
        }

        $check = $this->canDeleteFile($model);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'blocked' => true,
                'id'      => (int)$model->id,
                'refs'    => $check['refs'],
                'message' => 'File is referenced and cannot be removed.'
            ];
        }

        $res = StorageController::removeFile($model->id);
        return [
            'success' => (bool)($res['success'] ?? false),
            'id'      => (int)$model->id,
            'result'  => $res
        ];
    }

    /** BULK delete (JSON) - expects file_selected[] in POST */
    public function actionDeleteFiles()
    {
        if (!Yii::$app->request->isPost) {
            return ['success'=>false, 'error'=>'Bad Request'];
        }

        $ids = (array)Yii::$app->request->post('file_selected', []);
        if (!$ids) {
            return ['success'=>false, 'error'=>'No files selected'];
        }

        $deleted = [];
        $blocked = []; // has refs
        $failed  = []; // unexpected error or access denied

        foreach ($ids as $id) {
            $id = (int)$id;
            $model = $this->findFileByAccess($id);
            if (!$model) {
                $failed[] = ['id'=>$id, 'error'=>'not found/denied'];
                continue;
            }

            $check = $this->canDeleteFile($model);
            if (!$check['allowed']) {
                $blocked[] = ['id'=>$id, 'refs'=>$check['refs']];
                continue;
            }

            $res = StorageController::removeFile($model->id);
            if (!empty($res['success'])) {
                $deleted[] = $id;
            } else {
                $failed[] = ['id'=>$id, 'error'=>$res['message'] ?? 'unknown'];
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

}
