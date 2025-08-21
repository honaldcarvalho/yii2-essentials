<?php

namespace croacworks\essentials\controllers\rest;

use Exception;
use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use yii\imagine\Image;
use Imagine\Image\Box;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use croacworks\essentials\models\File;
use croacworks\essentials\models\Folder;
use croacworks\essentials\controllers\AuthorizationController;

class StorageController extends ControllerRest
{

    public function actionGetFile()
    {
        try {
            if ($this->request->isPost) {

                $post = $this->request->post();
                $file_name = $post['file_name'] ?? false;
                $description = $post['description'] ?? false;
                $id = $post['id'] ?? false;
                $file = null;
                $user_groups =  AuthorizationController::getUserGroups();

                if ($file_name) {
                    $file = File::find()->where(['name' => $file_name])->andWhere('or', ['in', 'group_id', $user_groups], ['group_id' => 1])->one();
                } else if ($description) {
                    $file = File::find()->where(['description' => $description])->andWhere('or', ['in', 'group_id', $user_groups], ['group_id' => 1])->one();
                } else if ($id) {
                    $file = File::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups], ['group_id' => 1]])->one();
                }

                if ($file !== null) {
                    return $file;
                } else {
                    throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not Found.'));
                }
            }
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
        }
    }

    public function actionListFiles()
    {
        try {
            if ($this->request->isPost) {

                $post = $this->request->post();
                $group_id = $post['group_id'] ?? null;
                $folder_id = $post['folder_id'] ?? null;
                $type = $post['type'] ?? null;
                $query = $post['query'] ?? false;
                $user_groups =  AuthorizationController::getUserGroups();

                $queryObj = File::find()->where(['or', ['like', 'name', $query], ['like', 'description', $query]]);
                // if ($group_id !== null) {
                //     $queryObj->andWhere(['group_id'=>$group_id]);
                // }
                if ($folder_id !== null) {
                    $queryObj->andWhere(['folder_id' => $folder_id]);
                }
                if ($type !== null) {
                    $queryObj->andWhere(['type' => $type]);
                }
                return $queryObj->andWhere(['or', ['group_id' => $group_id], ['group_id' => 1]])->all();
            }
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
        }
    }

    public function actionListFolder($id)
    {
        try {

            $user_groups = AuthorizationController::getUserByToken()->getUserGroupsId();
            $folder = Folder::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups], ['folder_id' => null]])->one();

            if ($folder !== null) {
                $folders = Folder::find()->where(['folder_id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups]])->one();
                $files = File::find()->where(['folder_id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups]])->all();
                return [
                    'folder' => $folder,
                    'folders' => $folders,
                    'files' => $files
                ];
            } else {
                throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not Found.'));
            }
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
        }
    }

    /**
     * Compress an image if it exceeds the maximum file size.
     * 
     * @param string $filePath Path to the uploaded image file.
     * @param int $maxFileSize Maximum file size in bytes.
     * @return string|false Path to the compressed image, or false on failure.
     */

    static function compressImage($filePath, $maxFileSize, $quality = 90)
    {
        try {
            // Get the current size of the image
            $fileSize = filesize($filePath);
            if ($fileSize <= 1 * 1024 * 1024) {
                return Image::getImagine()->open($filePath); // Return the original file path
            }
            do {
                // Open the image using Imagine
                $image = Image::getImagine()->open($filePath);

                // Get the current dimensions of the image
                $size = $image->getSize();

                // Reduce the dimensions by 10%
                $newSize = new Box($size->getWidth() * 0.9, $size->getHeight() * 0.9);

                // Resize the image
                $image->resize($newSize)
                    ->save($filePath, ['quality' => $quality]);

                // Recheck the file size after compression
                $fileSize = filesize($filePath);

                // Lower the quality slightly with each iteration
                $quality -= 10;
            } while ($fileSize > $maxFileSize && $quality > 10);

            return $image;
        } catch (\Throwable $th) {
            unlink($filePath);
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
        }
    }

    static function createThumbnail($srcImagePath, $destImagePath, $thumbWidth = 160, $thumbHeight = 99)
    {
        // Abre a imagem original
        $image = Image::getImagine()->open($srcImagePath);

        // Obtém as dimensões da imagem original
        $size = $image->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();

        // Calcula a proporção de aspecto da miniatura e da imagem original
        $aspectRatio = $thumbWidth / $thumbHeight;
        $imageRatio = $width / $height;

        // Define o novo tamanho para manter o aspect ratio
        if ($imageRatio > $aspectRatio) {
            // Se a imagem é mais larga que o aspecto da miniatura
            $newHeight = $height;
            $newWidth = (int)($height * $aspectRatio);
        } else {
            // Se a imagem é mais alta que o aspecto da miniatura
            $newWidth = $width;
            $newHeight = (int)($width / $aspectRatio);
        }

        // Calcula o ponto de corte para centralizar a imagem
        $src_x = ($width / 2) - ($newWidth / 2);
        $src_y = ($height / 2) - ($newHeight / 2);

        // Corta a imagem a partir do centro e redimensiona
        return Image::crop($srcImagePath, $newWidth, $newHeight, [$src_x, $src_y])
            ->resize(new Box($thumbWidth, $thumbHeight))
            ->save($destImagePath, ['quality' => 100]);
    }

    public static function uploadFile(
        $file,
        $options = [
            'file_name' => null, //custom file name
            'description' => null, //custom file description
            'folder_id' => 1, //common
            'group_id' => 1, //common
            'attact_model' => 0, //model name to atacct
            'attact_fields' => 0, //model atacct fields
            'attact_model_id' => 0, //model id to atacct
            'save' => 0, //salve model
            'convert_video' => 1, //convert video if not is mp4 type
            'thumb_aspect' => 1, //convert video if not is mp4 type
            'quality' => 80 //convert video if not is mp4 type
        ]
    ) {

        try {

            $webroot = Yii::getAlias('@webroot');
            $upload_folder = Yii::$app->params['upload.folder'];
            $web = Yii::getAlias('@web');

            $files_folder = "/{$upload_folder}";
            $upload_root = "{$webroot}{$files_folder}";
            $webFiles = "{$web}{$files_folder}";
            $temp_file = $file;
            $group_id = 1;
            $folder_id = null;
            $duration = 0;
            $save  = 0;
            $attact_model  = 0;
            $attact_model_id  = 0;
            $name = '';
            $description = '';
            $filePath = '';
            $filePathThumb = '';
            $fileUrl = '';
            $fileThumbUrl = '';
            $thumb_aspect = 0;
            $ext = '';
            $type = '';

            $model = new File();

            if (($temp_file = $file) !== null) {

                $file_name = isset($options['file_name']) ? $options['file_name'] : false;
                $description = isset($options['description']) ? $options['description'] : $temp_file->name;
                $folder_id = isset($options['folder_id']) ? $options['folder_id'] :  1;
                $attact_model = isset($options['attact_model']) ? json_decode($options['attact_model']) :  0;
                $save = isset($options['save']) ? $options['save'] :  0;
                $convert_video = isset($options['convert_video']) ? $options['convert_video'] : true;
                $thumb_aspect = isset($options['thumb_aspect']) ? $options['thumb_aspect'] :  1;
                $quality = isset($options['quality']) ? $options['quality'] :  1;

                $ext = $temp_file->extension;

                if (!empty($file_name)) {
                    $name = "{$file_name}.{$ext}";
                } else {
                    $name = 'file_' . date('dmYhims') . \Yii::$app->security->generateRandomString(6) . ".{$ext}";
                }

                $type = 'unknow';
                [$type, $format] = explode('/', $temp_file->type);

                if ($type == 'image') {

                    if ($folder_id === 1) {
                        $folder_id = 2;
                    }

                    $path = "{$files_folder}/images";
                    $pathThumb = "{$files_folder}/images/thumbs";
                    $pathRoot = "{$upload_root}/images";
                    $pathThumbRoot = "{$upload_root}/images/thumbs";

                    $filePath = "{$path}/{$name}";
                    $filePathThumb = "{$pathThumb}/{$name}";
                    $filePathRoot = "{$pathRoot}/{$name}";
                    $filePathThumbRoot = "{$pathThumbRoot}/{$name}";

                    $fileUrl = "{$webFiles}/images/{$name}";
                    $fileThumbUrl = "{$webFiles}/images/thumbs/{$name}";

                    if (!file_exists($pathRoot)) {
                        FileHelper::createDirectory($pathRoot);
                    }

                    if (!file_exists($pathThumbRoot)) {
                        FileHelper::createDirectory($pathThumbRoot);
                    }

                    $errors[] = $temp_file->saveAs($filePathRoot, ['quality' => $quality]);

                    if ($thumb_aspect == 1) {
                        $image_size = getimagesize($filePathRoot);
                        $major = $image_size[0]; //width
                        $min = $image_size[1]; //height
                        $mov = ($major - $min) / 2;
                        $point = [$mov, 0];

                        if ($major < $min) {
                            $major = $image_size[1];
                            $min = $image_size[0];
                            $mov = ($major - $min) / 2;
                            $point = [0, $mov];
                        }

                        $errors[] = Image::crop($filePathRoot, $min, $min, $point)
                            ->save($filePathThumbRoot, ['quality' => 100]);

                        if ($min > 300) {
                            $errors[] = Image::thumbnail($filePathThumbRoot, 300, 300)
                                ->save($filePathThumbRoot, ['quality' => 100]);
                        }
                    } else {
                        [$thumbWidth, $thumbHeigh] = explode('/', $options['thumb_aspect']);
                        $errors[] = self::createThumbnail($filePathRoot, $filePathThumbRoot, $thumbWidth, $thumbHeigh);
                    }
                } else if ($type == 'video') {

                    if ($folder_id === 1) {
                        $folder_id = 3;
                    }

                    if (!empty($file_name)) {
                        $name = "{$file_name}.mp4";
                    } else {
                        $name = 'file_' . date('dmYhims') . \Yii::$app->security->generateRandomString(6) . ".mp4";
                    }

                    $fileTemp = "{$upload_root}/{$temp_file->name}";

                    $path = "{$files_folder}/videos";
                    $pathRoot = "{$upload_root}/videos";
                    $filePath = "{$path}/{$name}";
                    $filePathRoot = "{$pathRoot}/{$name}";

                    $fileUrl = "{$webFiles}/videos/{$name}";

                    if (!file_exists($pathRoot)) {
                        FileHelper::createDirectory($pathRoot);
                    }

                    if ($convert_video && $ext != 'mp4') {
                        $errors[] = $temp_file->saveAs($fileTemp, ['quality' => $quality]);
                        $ffmpeg = FFMpeg::create();
                        $video = $ffmpeg->open($fileTemp);
                        $video->save(new X264(), $filePathRoot);
                        unlink($fileTemp);
                        $ext = 'mp4';
                    } else {
                        $errors[] = $temp_file->saveAs($filePathRoot, ['quality' => $quality]);
                    }

                    $sec = 2;
                    $video_thumb_name = str_replace('.', '_', $name) . '.jpg';
                    $pathThumb = "{$files_folder}/videos/thumbs";
                    $pathThumbRoot = "{$upload_root}/videos/thumbs";
                    $filePathThumb = "{$pathThumb}/{$video_thumb_name}";
                    $filePathThumbRoot = "{$pathThumbRoot}/{$video_thumb_name}";
                    $fileThumbUrl = "{$webFiles}/videos/thumbs/{$video_thumb_name}";

                    if (!file_exists($pathThumbRoot)) {
                        FileHelper::createDirectory($pathThumbRoot);
                    }

                    $ffmpeg = FFMpeg::create();
                    $video = $ffmpeg->open($filePathRoot);
                    $frame = $video->frame(TimeCode::fromSeconds($sec));
                    $frame->save($filePathThumbRoot);

                    if ($thumb_aspect == 1) {
                        $image_size = getimagesize($filePathThumbRoot);
                        $major = $image_size[0]; //width
                        $min = $image_size[1]; //height
                        $mov = ($major - $min) / 2;
                        $point = [$mov, 0];

                        if ($major < $min) {
                            $major = $image_size[1];
                            $min = $image_size[0];
                            $mov = ($major - $min) / 2;
                            $point = [0, $mov];
                        }

                        $errors[] = Image::crop($filePathThumbRoot, $min, $min, $point)
                            ->save($filePathThumbRoot, ['quality' => 100]);

                        if ($min > 300) {
                            $errors[] = Image::thumbnail($filePathThumbRoot, 300, 300)
                                ->save($filePathThumbRoot, ['quality' => 100]);
                        }
                    } else {
                        [$thumbWidth, $thumbHeigh] = explode('/', $options['thumb_aspect']);
                        $errors[] = self::createThumbnail($filePathThumbRoot, $filePathThumbRoot, $thumbWidth, $thumbHeigh);
                    }


                    $ffprobe = FFProbe::create();
                    $duration = $ffprobe
                        ->format($filePathRoot) // extracts file informations
                        ->get('duration');
                } else {
                    $type = 'doc';

                    if ($folder_id === 1) {
                        $folder_id = 4;
                    }

                    $path = "{$files_folder}/docs";
                    $pathRoot = "{$upload_root}/docs";
                    $filePath = "{$path}/{$name}";
                    $filePathRoot = "{$pathRoot}/{$name}";
                    $fileUrl = "{$webFiles}/docs/{$name}";
                    $fileThumbUrl = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW';
                    if (!file_exists($pathRoot)) {
                        FileHelper::createDirectory($pathRoot);
                    }

                    $errors[] = $temp_file->saveAs($filePathRoot, ['quality' => $quality]);
                }

                $file_uploaded = [
                    'group_id' => $group_id,
                    'folder_id' => $folder_id,
                    'name' => $name,
                    'description' => $description,
                    'path' => $filePath,
                    'url' => $fileUrl,
                    'pathThumb' => $filePathThumb,
                    'urlThumb' => $fileThumbUrl,
                    'extension' => $ext,
                    'type' => $type,
                    'size' => filesize($filePathRoot),
                    'duration' => intval($duration),
                    'created_at' => date('Y-m-d h:i:s')
                ];

                if ($save) {

                    $file_uploaded['group_id'] = $group_id; //common
                    if (!AuthorizationController::isAdmin())
                        $file_uploaded['group_id'] = AuthorizationController::userGroup();

                    $file_uploaded['class'] = File::class;
                    $file_uploaded['file'] = $temp_file;
                    $model = Yii::createObject($file_uploaded);

                    if ($model->save()) {
                        if ($attact_model) {
                            $attact = new $attact_model->class_name([
                                $attact_model->fields[0] => $attact_model->id,
                                $attact_model->fields[1] => $model->id
                            ]);
                            $attact->save();
                        }
                        return ['code' => 200, 'success' => true, 'data' => $model];
                    } else {
                        return ['code' => 200, 'success' => false, 'data' => $model->getErrors()];
                    }
                }
                return ['code' => 200, 'success' => true, 'data' => $file_uploaded];
            }
        } catch (\Exception $th) {
            return ['code' => 500, 'success' => false, 'data' => ["Unrecovery error..:" . $th->getMessage()]];
        }
    }

    public function actionSend()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            if (!($this->request->isPost) || ($temp_file = UploadedFile::getInstanceByName('file')) === null) {
                throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
            }

            $post = $this->request->post();

            // opções do upload
            $options = [];
            $options['file_name']     = $post['file_name']     ?? false;
            $options['description']   = $post['description']   ?? $temp_file->name;
            $options['folder_id']     = $post['folder_id']     ?? 1;
            $options['group_id']      = $post['group_id']      ?? 1;
            $options['save']          = $post['save']          ?? 0;
            $options['attact_model']  = $post['attact_model']  ?? false;
            $options['convert_video'] = $post['convert_video'] ?? true;
            $options['thumb_aspect']  = $post['thumb_aspect']  ?? 1;
            $options['quality']       = $post['quality']       ?? 80;

            // imagem: comprime temp
            [$type, $format] = explode('/', $temp_file->type);
            if ($type === 'image') {
                self::compressImage($temp_file->tempName, 5);
            }

            // faz upload (pode retornar data como objeto File OU array)
            $result = self::uploadFile($temp_file, $options);

            // Se não deu upload, devolve como está
            if (empty($result['success'])) {
                return $result;
            }

            // --- LINK DIRETO AO MODELO (novo) ---
            $linkClass = $post['model_class'] ?? null;
            $linkId    = $post['model_id']    ?? null;       // PK
            $linkField = $post['model_field'] ?? null;       // ex: 'file_id'
            $deleteOld = (int)($post['delete_old'] ?? 1);

            $linkRequested = !empty($linkClass) && $linkId !== null && !empty($linkField);

            if ($linkRequested) {
                // Extrai o ID do arquivo salvo (objeto ou array)
                $fileData = $result['data'] ?? null;
                $fileId   = 0;
                if (is_object($fileData) && isset($fileData->id)) {
                    $fileId = (int)$fileData->id;
                } elseif (is_array($fileData) && isset($fileData['id'])) {
                    $fileId = (int)$fileData['id'];
                }

                if ($fileId <= 0) {
                    $result['link'] = [
                        'linked' => false,
                        'error'  => 'Upload ok mas não retornou ID do arquivo.'
                    ];
                    return $result;
                }

                // tenta vincular
                $result['link'] = self::linkFileToModel($fileId, $linkClass, (int)$linkId, $linkField, $deleteOld);
            }

            return $result;
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
            return ['code' => 500, 'success' => false, 'data' => ['Exception' => $th->getMessage()]];
        }
    }

    /**
     * Vincula o arquivo ($fileId) ao modelo ($class::$id) no campo $field.
     * Se $deleteOld=1, remove o antigo quando diferente.
     */
    protected static function linkFileToModel(int $fileId, string $class, int $id, string $field, int $deleteOld = 1): array
    {
        try {
            if (!class_exists($class)) {
                return ['linked' => false, 'error' => "Class not found: {$class}"];
            }
            if (!is_subclass_of($class, \yii\db\ActiveRecord::class)) {
                return ['linked' => false, 'error' => "Class is not ActiveRecord: {$class}"];
            }

            /** @var \yii\db\ActiveRecord $model */
            $model = $class::findOne($id);
            if (!$model) {
                return ['linked' => false, 'error' => "Model id #{$id} not found for {$class}"];
            }
            if (!$model->hasAttribute($field)) {
                return ['linked' => false, 'error' => "Field '{$field}' not found in {$class}"];
            }

            $table = method_exists($class, 'tableName') ? $class::tableName() : '(unknown)';
            $oldId = (int)$model->getAttribute($field);

            // já está igual? então só confirma e sai
            if ($oldId === $fileId) {
                $after = (int)$class::find()->select($field)->where(['id' => $id])->scalar();
                return [
                    'linked'       => true,
                    'model_class'  => $class,
                    'model_id'     => $id,
                    'table'        => $table,
                    'field'        => $field,
                    'file_id'      => $fileId,
                    'old_id'       => $oldId,
                    'after'        => $after,
                    'updated_rows' => 0,
                    'removed_old'  => false,
                    'note'         => 'already linked'
                ];
            }

            // ⚠️ forçar update direto na coluna (sem validação/eventos)
            $updatedRows = 0;
            $tx = $model->getDb()->beginTransaction();
            try {
                $updatedRows = $model->updateAttributes([$field => $fileId]); // retorna nº de linhas atualizadas
                $tx->commit();
            } catch (\Throwable $e) {
                $tx->rollBack();
                return ['linked' => false, 'error' => 'updateAttributes failed: ' . $e->getMessage()];
            }

            // revalida no BD
            $after = (int)$class::find()->select($field)->where(['id' => $id])->scalar();

            // remover antigo se pedido
            $removed = false;
            if ($deleteOld && $oldId && $oldId !== $fileId) {
                $rm = self::removeFile($oldId);
                $removed = (bool)($rm['success'] ?? false);
            }

            return [
                'linked'       => ($after === $fileId),
                'model_class'  => $class,
                'model_id'     => $id,
                'table'        => $table,
                'field'        => $field,
                'file_id'      => $fileId,
                'old_id'       => $oldId,
                'after'        => $after,
                'updated_rows' => $updatedRows,
                'removed_old'  => $removed,
                'error'        => ($after === $fileId ? null : 'after-check mismatch')
            ];
        } catch (\Throwable $e) {
            return ['linked' => false, 'error' => $e->getMessage()];
        }
    }

    public static function removeFile($id)
    {
        try {

            $file = false;
            $success = false;
            $user_groups =  AuthorizationController::getUserGroups();

            if (!AuthorizationController::isAdmin()) {
                $model = File::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups]])->one();
            } else {
                $model = File::find()->where(['id' => $id])->andWhere(['or', ['in', 'group_id', $user_groups], ['in', 'group_id', [null, 1]]])->one();
            }

            if ($model !== null) {
                $id = $model->name;
                $file_name = $model->name;

                $message = "Could not remove model #{$id}";
                $thumb = "Could not remove thumb file {$file_name}.";
                $file = "Could not remove file {$file_name}.";

                if ($model->delete() !== false) {

                    $message = "Model #{$id} removed.";

                    if (@unlink(Yii::getAlias('@webroot') . $model->path))
                        $file = "File {$file_name} removed.";

                    if ($model->pathThumb) {
                        if (@unlink(Yii::getAlias('@webroot') . $model->pathThumb))
                            $thumb = "Thumb file {$file_name} removed.";
                    }
                } else {
                    return [
                        'code' => 500,
                        'success' => false,
                        'message' => $model->getErrors(),
                    ];
                }

                return [
                    'code' => 200,
                    'success' => true,
                    'message' => $message,
                    'file' => $file,
                    'thumb' => $thumb
                ];
            } else {
                return ['code' => 404, 'success' => false];
            }
        } catch (\Throwable $th) {
            return ['code' => 500, 'success' => false, 'data' => $th];
        }
    }

    public function actionRemoveFile($id)
    {
        try {
            if ($this->request->isPost) {
                return self::removeFile($id);
            }
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
        }
    }

    public function actionRemoveFiles()
    {
        $results = [];
        try {
            if ($this->request->isPost) {

                $post = $this->request->post();

                if (isset($post['keys'])) {
                    foreach ($post['keys'] as $key) {
                        $results[] = self::removeFile($key);
                    }
                    return $results;
                }
            }
            throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
        }
    }
}
