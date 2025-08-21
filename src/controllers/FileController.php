<?php

namespace croacworks\essentials\controllers;

use Yii;

use croacworks\essentials\models\File;
use yii\web\NotFoundHttpException;
use croacworks\essentials\models\UploadForm;
use yii\web\UploadedFile;
/**
 * FileController implements the CRUD actions for File model.
 */
class FileController extends AuthorizationController
{

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
                
        // if(!file_exists(\Yii::$app->basePath.'/web/preview.jpg') || !file_exists(\Yii::$app->basePath.'/web/preview_square.jpg') ){
        //     $data = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAD6Ab0DASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9wKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAxPiR8RtF+EPgDWPFHiO/j0vQdAtJL6/u3RnEEMalmbaoLMcDhVBJOAAScV5f+zN/wAFFPgz+2H4ovtE+HXje28Qavp1v9rntGsLuyl8ncFLqtxFGXAJAOzO3cucZGc7/gqT/wAo7/jD/wBizdf+g1+LH7Niax+wtovwN/aa0OO5vtFudZvtF8QwLyA6SyJJCMgKPOs3Ozrh4WbsBSw8lPEOlU0ilHXs5NpX8r2+8daLjQU4aybenflSbt52ufu7+0x+1p8Pv2PPBdn4h+I3iFfDmkaherp9vObK4uzLOUeQIEgjd/uxuckY468iu48M+I7Lxj4c0/V9NnW607VLaO8tZ1BAmikUOjAEAjKkHkZ5r83P+DjnxZp3j39hP4Za5o93Ff6TrPia2vrK6iOUuYJdPuXjdfZlYEfWuq/4KQ/tYax+yz/wR/8AAH/CNX8+l+I/Guj6PoNteW7lJ7KFrESzyxsDlW2R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1Tb7aL75xMIulGFLrGMZf4Yvb/wFdD61oooqSgooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAP//Z';
        //     $data_square = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkBAMAAACCzIhnAAAAG1BMVEX///8AAADf39+fn59/f3+/v79fX18fHx8/Pz8+HGFyAAAACXBIWXMAAA7EAAAOxAGVKw4bAAACIElEQVRYhe2WTU/jMBCGTUraHhlYkh4bUViOrcpKHFuoEMdUfB4JW9or1mp3ORIE/G4m37ZnYtQ9LnkvbW0/9ozteV0hGjVq1OhLyzmazcb9dYhrSPUnzCf4m+pp+aOWOIRCDxlS/u6d8EQLwF+Mx2cxjnnWEYB7FhlBL4toIOExQ3bTj6szCTBkCJwzLL5GKoJyI/jGIO1yAA4e6ohwJbdMABOybjXJ9yxWM5W+BXFhhyJRz2xRERGTbmzzrMjIXxuZw9pIwCCf5DJnAhuRaXSEST8gh6UhEXP8XXJYGiJ3BZELZv4q0oY7iojIbFWRkd9nkA54erOCHMBvhhCOhF81yADYRZIN0JmixC5W4E9YIjka8EIVKWufq8lsTITlf8IgP8M6RDgr7H+rED8xpYTxaywm0TH2v+i5iP2juM5iUqG9FBWtbPIxbzG5WjH0CCJuWYspmdz5jGtpWwbdxKNIG7YsCF6DkCCOtEWGpfNMEDFlyrLSZn5TNCQgPqdJehTZsOYvIp8iHTuSWYeOdIlna5qncZur/ANiDYxDAqivAMHnQh8TTVNmx7jXQu2m5+Jar3LxZGkI+/JV6sK2iTgxs2GH1YZMsyNQkRtiv6ig9Mp2bnR6Id8xSOFWLZnFVSLOJdoFl/wmesv7+OriHC0ozJHUlGTiSoZd5xpI4z+O8rfnlSVwb1eg2VyO+E8Ly1XZmy0XpzUTNmrUqNF/pA8Dt1LBNnanTAAAAABJRU5ErkJggg==';
        //     $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
        //     $data_square = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data_square));
        //     file_put_contents(\Yii::$app->basePath.'/web/preview.jpg', $data);    
        //     file_put_contents(\Yii::$app->basePath.'/web/preview_square.jpg', $data_square);    
        // }
        
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

    public function actionForm()
    {
        if ($this->request->isPost){
            dd($this->request->post());
        }
        return $this->render('upload');
    }

   public function actionMultiple()
   {
       return $this->render('multiple');
   }
    /**
     * Creates a new File model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new File();
        $uploadForm = new UploadForm();

        if ($this->request->isPost && $model->load($this->request->post())) {
            
            $uploadForm->file = UploadedFile::getInstance($model, 'file');

            $fileUploaded = $uploadForm->uploadFile(['thumb'=>true,'folderByType'=>true]);

            if($fileUploaded){
                
                $fileUploaded = (object) $fileUploaded;
                $model->name = $fileUploaded->name;
                if(empty(trim($model->description))) $model->description = $fileUploaded->description;
                $model->group_id = $this::getUserGroups()[0];
                $model->folder_id = $model->folder_id == null ? ($fileUploaded->type == 'img' ? 2 : ($fileUploaded->type == 'vid' ? 3 : 4)) : $model->folder_id;
                $model->file = file_get_contents($fileUploaded->path);
                $model->path= $fileUploaded->path;
                $model->url= $fileUploaded->url;
                $model->pathThumb= $fileUploaded->pathThumb;
                $model->urlThumb= $fileUploaded->urlThumb;
                $model->type= $fileUploaded->type;
                $model->extension = $fileUploaded->extension;
                $model->size = $fileUploaded->size;
            } 

            if ($fileUploaded && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'accept'=> '.'.implode(',',$model->extensions)
        ]);
    }

    public function actionCrop()
    {
        $model = new File();
        $uploadForm = new UploadForm();
        $model->extensions =  ['jpg','png'];

        if ($this->request->isPost && $model->load($this->request->post())) {
            
            $uploadForm->file = UploadedFile::getInstance($model, 'file');
            $fileUploaded = $uploadForm->uploadFile(['thumb'=>true,'folderByType'=>true]);

            if($fileUploaded){
                
                $fileUploaded = (object) $fileUploaded;
                if(empty(trim($model->description))) {$model->description = $fileUploaded->description;}
                $model->file = $fileUploaded->path;
                $model->name = $fileUploaded->name;
                $model->path = $fileUploaded->path;
                $model->url= $fileUploaded->url;
                $model->pathThumb= $fileUploaded->pathThumb;
                $model->urlThumb= $fileUploaded->urlThumb;
                $model->extension = $fileUploaded->extension;
                $model->size = $fileUploaded->size;
            } 

            if ($fileUploaded && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('image', [
            'model' => $model,
            'accept'=> '.'.implode(',.',$model->extensions)
        ]);
    }

    /**
     * Updates an existing File model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $uploadForm = new UploadForm();
        $model->extensions =  [$model->extension];

        if ($this->request->isPost) {

            $model->load($this->request->post());
            $uploadForm->file = UploadedFile::getInstance($model, 'file');

            if(isset($uploadForm->file)){

                $fileUploaded = $uploadForm->uploadFile(['thumb'=>true]);
                if($fileUploaded){
                    $fileUploaded = (object) $fileUploaded;
                    try{
                        unlink($model->path);
                        if($model->pathThumb){
                            unlink($model->pathThumb);
                        }
                    }catch(\Exception $e){
                        //null
                    }
                    $model->name = $fileUploaded->name;
                    if(empty(trim($model->description))) {$model->description = $fileUploaded->name;}
                    $model->path= $fileUploaded->path;
                    $model->url= $fileUploaded->url;
                    $model->pathThumb= $fileUploaded->pathThumb;
                    $model->urlThumb= $fileUploaded->urlThumb;
                    $model->extension = $fileUploaded->extension;
                    $model->size = $fileUploaded->size;
                }
            } 

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('update', [
            'model' => $model,
            'accept'=> '.'.implode(',.',$model->extensions)
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

    public function actionDeleteFiles()
    {   

        if(Yii::$app->request->isPost){

            $files_id = Yii::$app->request->post()['file_selected'];
            $noDeleted = '';
            $deleted = '';
            $name = 'undefined';

            foreach($files_id as $file_id){
                try {
                    
                    if($this::isAdmin()){
                        $model = File::find()->where(['id'=>$file_id])->one();
                    }else{
                        $model = File::find()->where(['id'=>$file_id])->andWhere(['or',['in','group_id',$this::getUserGroups()]])->one();
                    }
                    $name = $model->name;
                    if($model != null){
                        if($model->delete()){
                            @unlink($model->path);
                            $deleted .= "({$name}) ";
                            if($model->pathThumb){
                                @unlink($model->pathThumb);
                            }
                        }else{
                            $noDeleted .= "($name) ";
                        }
                    }
                } catch (\Throwable $th) {  
                    $noDeleted .=  "(File #{$file_id}) ";
                }

            }           

            if(!empty($deleted))
                Yii::$app->session->setFlash("success", Yii::t('app', 'Files deleted: ').$deleted);

            if(!empty($noDeleted))
                Yii::$app->session->setFlash("danger", Yii::t('app', 'Fail on deleted: ').$noDeleted);
        }

        if(($folder_id = Yii::$app->request->get('folder')) !== null)
            return $this->redirect(['folder/view', 'id' => $folder_id]);
        else
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
    
    public function actionDelete($id)
    {
        $result = $this->remove($id);

        if(isset($result['folder_id']) && $result['folder_id']){
            return $this->redirect(["/folder/view/{$result['folder_id']}"]);   
        }else{
            return $this->redirect(["index"]);
        }

    }

    public function actionList()
    {

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $files = [];
        $request = Yii::$app->request;
        $str_find = $request->post('str_search');
        $folder_id = $request->post('folder_id');
        $extensions = $request->post('extensions');
        $count = $request->post('count');

        if(!isset($str_find) || !empty($str_find)){
            $files = File::find()->where(['like','name',"$str_find"])
            ->orWhere(['like','description',"$str_find"])
            ->andWhere(['in','extension',$extensions])
            ->andWhere(['or',['in','group_id',$this::getUserGroups()],['group_id'=>null],['group_id'=>1]]);

            if(isset($folder_id) && !empty($folder_id) ) {
                $files =  $files->andWhere(['folder_id'=>$folder_id]);
            }

            $files =  $files->orderBy(['created_at'=>SORT_DESC])->asArray()->all();
           
        }else{
            
            $files = File::find()->where(['in','extension',$extensions]);
            if(isset($folder_id) && !empty($folder_id) ) {
                $files =  $files->andWhere(['folder_id'=>$folder_id]);
            }
            $files =  $files->offset($count)->orderBy(['created_at'=>SORT_DESC])->limit(10)->asArray()->all();
        }
        
        return $files; 
    }


}
