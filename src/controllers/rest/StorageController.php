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
    /**
     * Standard error payload builder.
     * Adds caller (who called errorResponse) and, if provided, the Throwable file/line.
     */
    private static function errorResponse(
        int $code,
        string $type,
        string $message,
        array $context = []
    ): array {
        // Captura a 1ª frame do chamador real (ignora a própria errorResponse)
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $caller = null;
        foreach ($bt as $frame) {
            if (($frame['function'] ?? null) !== __FUNCTION__) {
                $caller = $frame;
                break;
            }
        }

        $payload = [
            'code'    => $code,
            'success' => false,
            'error'   => [
                'type'    => $type,
                'message' => Yii::t('app', $message),
                'context' => $context,
                'file'    => $caller['file'] ?? null,
                'line'    => $caller['line'] ?? null,
            ],
        ];

        if (defined('YII_ENV_DEV') && YII_ENV_DEV) {
            $payload['error']['env'] = 'dev';
        }

        return $payload;
    }


    /**
     * Map any \Throwable into a standard error payload (with file/line).
     */
    private static function mapException(\Throwable $e, array $context = []): array
    {
        // Você pode distinguir tipos aqui (db, image, video...) – mantive simples:
        $resp = self::errorResponse(
            500,
            'unhandled_exception',
            $e->getMessage(), // use a própria mensagem do Throwable
            ['exception' => get_class($e), 'detail' => $e->getMessage()] + $context
        );

        // Força o ponto exato do erro
        $resp['error']['file'] = $e->getFile();
        $resp['error']['line'] = $e->getLine();

        if (defined('YII_ENV_DEV') && YII_ENV_DEV) {
            $resp['error']['trace'] = $e->getTraceAsString();
        }
        return $resp;
    }


    /**
     * Translate PHP upload error code.
     */
    private static function phpUploadErrorName(int $code): string
    {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'UPLOAD_ERR_INI_SIZE (File exceeds upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'UPLOAD_ERR_FORM_SIZE (File exceeds the MAX_FILE_SIZE form limit).',
            UPLOAD_ERR_PARTIAL    => 'UPLOAD_ERR_PARTIAL (The uploaded file was only partially uploaded).',
            UPLOAD_ERR_NO_FILE    => 'UPLOAD_ERR_NO_FILE (No file was uploaded).',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR (Missing a temporary folder).',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE (Failed to write file to disk).',
            UPLOAD_ERR_EXTENSION  => 'UPLOAD_ERR_EXTENSION (A PHP extension stopped the file upload).',
            0                     => 'OK',
        ];
        return $map[$code] ?? "UNKNOWN ({$code})";
    }

    /**
     * Diagnose filesystem write failure for better error messages.
     */
    private static function diagnoseWriteFailure(string $targetPath, ?\yii\web\UploadedFile $file = null): array
    {
        $dir = dirname($targetPath);
        return [
            'target'          => $targetPath,
            'dir'             => $dir,
            'dir_exists'      => is_dir($dir),
            'dir_writable'    => is_dir($dir) ? is_writable($dir) : false,
            'file_error'      => $file ? $file->error : null,
            'file_error_name' => $file ? self::phpUploadErrorName((int)$file->error) : null,
            'free_space'      => @disk_free_space($dir) ?: null,
            'uid'             => function_exists('posix_geteuid') ? @posix_geteuid() : null,
            'user'            => function_exists('posix_getpwuid') && function_exists('posix_geteuid')
                ? (@posix_getpwuid(@posix_geteuid())['name'] ?? null) : null,
        ];
    }

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
            return self::mapException($th, ['stage' => 'actionGetFile.catch']);
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

                $queryObj = File::find()->where(['or', ['like', 'name', $query], ['like', 'description', $query]]);
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
            return self::mapException($th, ['stage' => 'actionListFiles.catch']);
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
            return self::mapException($th, ['stage' => 'actionListFolder.catch']);
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
            $fileSize = filesize($filePath);
            if ($fileSize <= $maxFileSize) {
                return Image::getImagine()->open($filePath);
            }
            do {
                $image = Image::getImagine()->open($filePath);
                $size = $image->getSize();
                $newSize = new Box($size->getWidth() * 0.9, $size->getHeight() * 0.9);

                $image->resize($newSize)
                    ->save($filePath, ['quality' => $quality]);

                $fileSize = filesize($filePath);
                $quality -= 10;
            } while ($fileSize > $maxFileSize && $quality > 10);

            return $image;
        } catch (\Throwable $th) {
            @unlink($filePath);
            throw new \yii\web\BadRequestHttpException(
                Yii::t('app', 'Failed to compress image: ') . $th->getMessage(),
                0,
                $th
            );
        }
    }

    static function createThumbnail($srcImagePath, $destImagePath, $thumbWidth = 160, $thumbHeight = 99)
    {
        $image = Image::getImagine()->open($srcImagePath);

        $size = $image->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();

        $aspectRatio = $thumbWidth / $thumbHeight;
        $imageRatio = $width / $height;

        if ($imageRatio > $aspectRatio) {
            $newHeight = $height;
            $newWidth = (int)($height * $aspectRatio);
        } else {
            $newWidth = $width;
            $newHeight = (int)($width / $aspectRatio);
        }

        $src_x = ($width / 2) - ($newWidth / 2);
        $src_y = ($height / 2) - ($newHeight / 2);

        return Image::crop($srcImagePath, $newWidth, $newHeight, [$src_x, $src_y])
            ->resize(new Box($thumbWidth, $thumbHeight))
            ->save($destImagePath, ['quality' => 100]);
    }

    public static function uploadFile(
        $file,
        $options = [
            'file_name'       => null,
            'description'     => null,
            'folder_id'       => 1,
            'group_id'        => 1,
            'attach_model'    => 0,
            'attact_fields'   => 0,
            'attact_model_id' => 0,
            'save'            => 0,
            'convert_video'   => 1,
            'thumb_aspect'    => 1,
            'quality'         => 80
        ]
    ) {
        $safeUnlink = function (?string $p) {
            if ($p && is_file($p)) {
                @unlink($p);
            }
        };

        $createdPaths = [
            'file'  => null,
            'thumb' => null,
            'temp'  => null,
        ];

        $response = ['code' => 500, 'success' => false, 'data' => []];

        try {
            $webroot       = Yii::getAlias('@webroot');
            $web           = Yii::getAlias('@web');
            $upload_folder = Yii::$app->params['upload.folder'];

            $files_folder  = "/{$upload_folder}";
            $upload_root   = "{$webroot}{$files_folder}";
            $webFiles      = "{$web}{$files_folder}";

            $temp_file     = $file;
            $group_id      = 1;
            $folder_id     = null;
            $duration      = 0;
            $save          = 0;
            $attach_model  = 0;
            $name          = '';
            $description   = '';
            $filePath      = '';
            $filePathThumb = '';
            $fileUrl       = '';
            $fileThumbUrl  = '';
            $thumb_aspect  = 0;
            $ext           = '';
            $type          = '';

            $model         = new File();

            if (($temp_file = $file) === null) {
                $response = self::errorResponse(400, 'upload.no_file', 'No file received.', []);
                return $response;
            }

            $file_name     = $options['file_name']       ?? false;
            $description   = $options['description']     ?? $temp_file->name;
            $folder_id     = $options['folder_id']       ?? 1;
            $attach_model  = isset($options['attach_model']) ? json_decode($options['attach_model']) : 0;
            $save          = $options['save']            ?? 0;
            $convert_video = $options['convert_video']   ?? true;
            $thumb_aspect  = $options['thumb_aspect']    ?? 1;
            $quality       = $options['quality']         ?? 80;

            $ext = $temp_file->extension ?: pathinfo($temp_file->name, PATHINFO_EXTENSION);

            if (!empty($file_name)) {
                $name = "{$file_name}.{$ext}";
            } else {
                $name = 'file_' . date('dmYHis') . Yii::$app->security->generateRandomString(6) . ".{$ext}";
            }

            $type = 'unknow';
            if (!empty($temp_file->type) && strpos($temp_file->type, '/') !== false) {
                [$type, $format] = explode('/', $temp_file->type);
            }

            // ======== IMAGE ========
            if ($type === 'image') {
                if ($folder_id === 1) {
                    $folder_id = 2;
                }

                $path          = "{$files_folder}/images";
                $pathThumb     = "{$files_folder}/images/thumbs";
                $pathRoot      = "{$upload_root}/images";
                $pathThumbRoot = "{$upload_root}/images/thumbs";

                $filePath         = "{$path}/{$name}";
                $filePathThumb    = "{$pathThumb}/{$name}";
                $filePathRoot     = "{$pathRoot}/{$name}";
                $filePathThumbRoot = "{$pathThumbRoot}/{$name}";

                $fileUrl       = "{$webFiles}/images/{$name}";
                $fileThumbUrl  = "{$webFiles}/images/thumbs/{$name}";

                if (!file_exists($pathRoot)) {
                    FileHelper::createDirectory($pathRoot);
                }
                if (!file_exists($pathThumbRoot)) {
                    FileHelper::createDirectory($pathThumbRoot);
                }

                if (!$temp_file->saveAs($filePathRoot, ['quality' => $quality])) {
                    return self::errorResponse(
                        500,
                        'filesystem.write_failed',
                        'Failed to save original image.',
                        self::diagnoseWriteFailure($filePathRoot, $temp_file)
                    );
                }
                $createdPaths['file'] = $filePathRoot;

                try {
                    if ($thumb_aspect == 1) {
                        $image_size = getimagesize($filePathRoot);
                        if (!$image_size) {
                            return self::errorResponse(
                                422,
                                'image.get_size_failed',
                                'Failed to read image size.',
                                ['input' => $filePathRoot]
                            );
                        }

                        $major = $image_size[0];
                        $min   = $image_size[1];
                        $mov   = ($major - $min) / 2;
                        $point = [$mov, 0];

                        if ($major < $min) {
                            $major = $image_size[1];
                            $min   = $image_size[0];
                            $mov   = ($major - $min) / 2;
                            $point = [0, $mov];
                        }

                        Image::crop($filePathRoot, $min, $min, $point)
                            ->save($filePathThumbRoot, ['quality' => 100]);
                        $createdPaths['thumb'] = $filePathThumbRoot;

                        if ($min > 300) {
                            Image::thumbnail($filePathThumbRoot, 300, 300)
                                ->save($filePathThumbRoot, ['quality' => 100]);
                        }
                    } else {
                        [$thumbWidth, $thumbHeigh] = explode('/', $options['thumb_aspect']);
                        self::createThumbnail($filePathRoot, $filePathThumbRoot, (int)$thumbWidth, (int)$thumbHeigh);
                        $createdPaths['thumb'] = $filePathThumbRoot;
                    }
                } catch (\Throwable $e) {
                    $safeUnlink($createdPaths['thumb']);
                    return self::mapException($e, ['stage' => 'image.thumb', 'input' => $filePathRoot, 'output' => $filePathThumbRoot]);
                }

                // ======== VIDEO ========
            } elseif ($type === 'video') {
                if ($folder_id === 1) {
                    $folder_id = 3;
                }

                if (!empty($file_name)) {
                    $name = "{$file_name}.mp4";
                } else {
                    $name = 'file_' . date('dmYHis') . Yii::$app->security->generateRandomString(6) . ".mp4";
                }

                $fileTemp = "{$upload_root}/{$temp_file->name}";

                $path     = "{$files_folder}/videos";
                $pathRoot = "{$upload_root}/videos";
                $filePath     = "{$path}/{$name}";
                $filePathRoot = "{$pathRoot}/{$name}";
                $fileUrl  = "{$webFiles}/videos/{$name}";

                if (!file_exists($pathRoot)) {
                    FileHelper::createDirectory($pathRoot);
                }

                if ($convert_video && strtolower($ext) !== 'mp4') {
                    if (!$temp_file->saveAs($fileTemp, ['quality' => $quality])) {
                        return self::errorResponse(
                            500,
                            'filesystem.write_failed',
                            'Failed to save temporary video file.',
                            self::diagnoseWriteFailure($fileTemp, $temp_file)
                        );
                    }
                    $createdPaths['temp'] = $fileTemp;

                    try {
                        $ffmpeg = FFMpeg::create();
                        $video  = $ffmpeg->open($fileTemp);
                        $video->save(new X264(), $filePathRoot);
                    } catch (\Throwable $e) {
                        $safeUnlink($createdPaths['temp']);
                        $safeUnlink($filePathRoot);
                        $createdPaths['temp'] = null;
                        return self::mapException($e, ['stage' => 'video.encode', 'input' => $fileTemp, 'output' => $filePathRoot]);
                    }

                    $safeUnlink($createdPaths['temp']);
                    $createdPaths['temp'] = null;
                    $ext = 'mp4';
                } else {
                    if (!$temp_file->saveAs($filePathRoot, ['quality' => $quality])) {
                        return self::errorResponse(
                            500,
                            'filesystem.write_failed',
                            'Failed to save video.',
                            self::diagnoseWriteFailure($filePathRoot, $temp_file)
                        );
                    }
                }
                $createdPaths['file'] = $filePathRoot;

                $sec = 2;
                $video_thumb_name  = str_replace('.', '_', $name) . '.jpg';
                $pathThumb         = "{$files_folder}/videos/thumbs";
                $pathThumbRoot     = "{$upload_root}/videos/thumbs";
                $filePathThumb     = "{$pathThumb}/{$video_thumb_name}";
                $filePathThumbRoot = "{$pathThumbRoot}/{$video_thumb_name}";
                $fileThumbUrl      = "{$webFiles}/videos/thumbs/{$video_thumb_name}";

                if (!file_exists($pathThumbRoot)) {
                    FileHelper::createDirectory($pathThumbRoot);
                }

                try {
                    $ffmpeg = FFMpeg::create();
                    $video  = $ffmpeg->open($filePathRoot);
                    $frame  = $video->frame(TimeCode::fromSeconds($sec));
                    $frame->save($filePathThumbRoot);
                    $createdPaths['thumb'] = $filePathThumbRoot;

                    if ($thumb_aspect == 1) {
                        $image_size = getimagesize($filePathThumbRoot);
                        if (!$image_size) {
                            return self::errorResponse(
                                422,
                                'image.get_size_failed',
                                'Failed to read video thumbnail size.',
                                ['input' => $filePathThumbRoot]
                            );
                        }

                        $major = $image_size[0];
                        $min   = $image_size[1];
                        $mov   = ($major - $min) / 2;
                        $point = [$mov, 0];

                        if ($major < $min) {
                            $major = $image_size[1];
                            $min   = $image_size[0];
                            $mov   = ($major - $min) / 2;
                            $point = [0, $mov];
                        }

                        Image::crop($filePathThumbRoot, $min, $min, $point)
                            ->save($filePathThumbRoot, ['quality' => 100]);

                        if ($min > 300) {
                            Image::thumbnail($filePathThumbRoot, 300, 300)
                                ->save($filePathThumbRoot, ['quality' => 100]);
                        }
                    } else {
                        [$thumbWidth, $thumbHeigh] = explode('/', $options['thumb_aspect']);
                        self::createThumbnail($filePathThumbRoot, $filePathThumbRoot, (int)$thumbWidth, (int)$thumbHeigh);
                    }
                } catch (\Throwable $e) {
                    $safeUnlink($createdPaths['thumb']);
                    return self::mapException($e, ['stage' => 'video.thumb', 'input' => $filePathRoot, 'output' => $filePathThumbRoot]);
                }

                try {
                    $ffprobe  = \FFMpeg\FFProbe::create();
                    $duration = (int)$ffprobe->format($filePathRoot)->get('duration');
                } catch (\Throwable $e) {
                    $duration = 0;
                }

                // ======== DOC ========
            } else {
                $type = 'doc';
                if ($folder_id === 1) {
                    $folder_id = 4;
                }

                $path     = "{$files_folder}/docs";
                $pathRoot = "{$upload_root}/docs";
                $filePath     = "{$path}/{$name}";
                $filePathRoot = "{$pathRoot}/{$name}";
                $fileUrl      = "{$webFiles}/docs/{$name}";
                $fileThumbUrl = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW';

                if (!file_exists($pathRoot)) {
                    FileHelper::createDirectory($pathRoot);
                }

                if (!$temp_file->saveAs($filePathRoot, ['quality' => $quality])) {
                    return self::errorResponse(
                        500,
                        'filesystem.write_failed',
                        'Failed to save document.',
                        self::diagnoseWriteFailure($filePathRoot, $temp_file)
                    );
                }
                $createdPaths['file'] = $filePathRoot;
            }

            $file_uploaded = [
                'group_id'   => $group_id,
                'folder_id'  => $folder_id,
                'name'       => $name,
                'description' => $description,
                'path'       => $filePath,
                'url'        => $fileUrl,
                'pathThumb'  => $filePathThumb,
                'urlThumb'   => $fileThumbUrl,
                'extension'  => $ext,
                'type'       => $type,
                'size'       => filesize($createdPaths['file'] ?? $filePathRoot),
                'duration'   => (int)$duration,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($save) {
                $file_uploaded['group_id'] = $group_id;
                if (!AuthorizationController::isMaster()) {
                    $file_uploaded['group_id'] = AuthorizationController::userGroup();
                }

                $file_uploaded['class'] = File::class;
                $file_uploaded['file']  = $temp_file;

                /** @var File $model */
                $model = Yii::createObject($file_uploaded);

                if (!$model->save()) {
                    $safeUnlink($createdPaths['file']);
                    $safeUnlink($createdPaths['thumb']);

                    return self::errorResponse(
                        422,
                        'db.validation_failed',
                        'Failed to save file in database.',
                        [
                            'firstErrors' => $model->getFirstErrors(),
                            'allErrors'   => $model->getErrors(),
                            'attributes'  => $model->getAttributes(null, ['file']),
                        ]
                    );
                }

                if ($attach_model) {
                    $attact = new $attach_model->class_name([
                        $attach_model->fields[0] => $attach_model->id,
                        $attach_model->fields[1] => $model->id
                    ]);
                    if (!$attact->save()) {
                        Yii::warning(['attach_error' => $attact->getErrors()], __METHOD__);
                    }
                }

                $response = ['code' => 200, 'success' => true, 'data' => $model];
                return $response;
            }

            $response = ['code' => 200, 'success' => true, 'data' => $file_uploaded];
            return $response;
        } catch (\Throwable $th) {
            $safeUnlink($createdPaths['file']);
            $safeUnlink($createdPaths['thumb']);
            $safeUnlink($createdPaths['temp']);

            Yii::error("uploadFile error: {$th->getMessage()}\n{$th->getTraceAsString()}", __METHOD__);
            return self::mapException($th, ['stage' => 'uploadFile.catch']);
        }
    }

    public function actionSend()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            if (!($this->request->isPost) || ($temp_file = UploadedFile::getInstanceByName('file')) === null) {
                throw new \yii\web\BadRequestHttpException(Yii::t('app', 'Bad Request.'));
            }

            if ($temp_file->error !== UPLOAD_ERR_OK) {
                $err = self::errorResponse(
                    400,
                    'upload.php_error',
                    'PHP upload error.',
                    [
                        'php_error'      => $temp_file->error,
                        'php_error_name' => self::phpUploadErrorName((int)$temp_file->error),
                    ]
                );
                \Yii::$app->response->statusCode = 400;
                return $err;
            }

            $post = $this->request->post();

            $options = [];
            $options['file_name']     = $post['file_name']     ?? false;
            $options['description']   = $post['description']   ?? $temp_file->name;
            $options['folder_id']     = $post['folder_id']     ?? 1;
            $options['group_id']      = $post['group_id']      ?? 1;
            $options['save']          = $post['save']          ?? 0;
            $options['attach_model']  = $post['attach_model']  ?? false;
            $options['convert_video'] = $post['convert_video'] ?? true;
            $options['thumb_aspect']  = $post['thumb_aspect']  ?? 1;
            $options['quality']       = $post['quality']       ?? 80;

            [$type, $format] = explode('/', $temp_file->type);
            if ($type === 'image') {
                self::compressImage($temp_file->tempName, 5 * 1024 * 1024);
            }

            $result = self::uploadFile($temp_file, $options);

            if (empty($result['success'])) {
                \Yii::$app->response->statusCode = (int)($result['code'] ?? 500);
                return $result;
            }

            $linkClass = $post['model_class'] ?? null;
            $linkId    = $post['model_id']    ?? null;
            $linkField = $post['model_field'] ?? null;
            $deleteOld = (int)($post['delete_old'] ?? 1);

            $linkRequested = !empty($linkClass) && $linkId !== null && !empty($linkField);

            if ($linkRequested) {
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
                        'error'  => Yii::t('app', 'Upload succeeded but file ID was not returned.'),
                    ];
                    return $result;
                }

                $result['link'] = self::linkFileToModel($fileId, $linkClass, (int)$linkId, $linkField, $deleteOld);
            }

            return $result;
        } catch (\Throwable $th) {
            AuthorizationController::error($th);
            $err = self::mapException($th, ['stage' => 'actionSend.catch']);
            \Yii::$app->response->statusCode = (int)($err['code'] ?? 500);
            return $err;
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
                return ['linked' => false, 'error' => Yii::t('app', "Class not found: {class}", ['class' => $class])];
            }
            if (!is_subclass_of($class, \yii\db\ActiveRecord::class)) {
                return ['linked' => false, 'error' => Yii::t('app', "Class is not ActiveRecord: {class}", ['class' => $class])];
            }

            /** @var \yii\db\ActiveRecord $model */
            $model = $class::findOne($id);
            if (!$model) {
                return ['linked' => false, 'error' => Yii::t('app', "Model id #{id} not found for {class}", ['id' => $id, 'class' => $class])];
            }
            if (!$model->hasAttribute($field)) {
                return ['linked' => false, 'error' => Yii::t('app', "Field '{field}' not found in {class}", ['field' => $field, 'class' => $class])];
            }

            $table = method_exists($class, 'tableName') ? $class::tableName() : '(unknown)';
            $oldId = (int)$model->getAttribute($field);

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

            $updatedRows = 0;
            $tx = $model->getDb()->beginTransaction();
            try {
                $updatedRows = $model->updateAttributes([$field => $fileId]);
                $tx->commit();
            } catch (\Throwable $e) {
                $tx->rollBack();
                return ['linked' => false, 'error' => Yii::t('app', 'updateAttributes failed: {msg}', ['msg' => $e->getMessage()])];
            }

            $after = (int)$class::find()->select($field)->where(['id' => $id])->scalar();

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

    public static function removeFile($id, array $opts = [])
    {
        try {
            $force         = (bool)($opts['force'] ?? false);   // permite forçar bypass (ex.: para master)
            $ignoreMissing = (bool)($opts['ignoreMissing'] ?? true); // não falhar se arquivo físico não existir
            $deleteThumb   = (bool)($opts['deleteThumb'] ?? true);

            // ===== 1) Localiza o modelo =====
            if ($force || AuthorizationController::isMaster()) {
                // Bypass total de escopo/grupo
                $model = \croacworks\essentials\models\File::find(false)
                    ->where(['id' => (int)$id])->one();
            } else {
                // Respeita os grupos do usuário
                $user_groups = AuthorizationController::getUserGroups();
                $model = \croacworks\essentials\models\File::find()
                    ->where(['id' => (int)$id])
                    ->andWhere(['in', 'group_id', (array)$user_groups])
                    ->one();
            }

            if ($model === null) {
                return ['code' => 404, 'success' => false, 'message' => 'file_not_found_or_access_denied'];
            }

            // Guarda nomes/caminhos antes do delete
            $fileName   = $model->name;
            $absPath    = Yii::getAlias('@webroot') . ($model->path ?? '');
            $absThumb   = $model->pathThumb ? Yii::getAlias('@webroot') . $model->pathThumb : null;

            $message = Yii::t('app', "Could not remove model #{id}", ['id' => $fileName]);
            $thumb   = Yii::t('app', "Could not remove thumb file {file}.", ['file' => $fileName]);
            $file    = Yii::t('app', "Could not remove file {file}.", ['file' => $fileName]);

            // ===== 2) Remove do banco (mantendo sua ordem original) =====
            if ($model->delete() === false) {
                return [
                    'code'    => 500,
                    'success' => false,
                    'message' => $model->getErrors(),
                ];
            }

            $message = Yii::t('app', "Model #{id} removed.", ['id' => $fileName]);

            // ===== 3) Remove arquivo físico (ignora se não existir quando $ignoreMissing=true) =====
            if (is_string($absPath) && $absPath !== '') {
                if (is_file($absPath)) {
                    if (@unlink($absPath)) {
                        $file = Yii::t('app', "File {file} removed.", ['file' => $fileName]);
                    }
                } elseif ($ignoreMissing) {
                    // ok, mantém sucesso mesmo sem arquivo físico
                    $file = Yii::t('app', "File {file} not found, skipped.", ['file' => $fileName]);
                }
            }

            // ===== 4) Remove thumb (se existir) =====
            if ($deleteThumb && $absThumb) {
                if (is_file($absThumb)) {
                    if (@unlink($absThumb)) {
                        $thumb = Yii::t('app', "Thumb file {file} removed.", ['file' => $fileName]);
                    }
                } elseif ($ignoreMissing) {
                    $thumb = Yii::t('app', "Thumb file {file} not found, skipped.", ['file' => $fileName]);
                }
            }

            return [
                'code'    => 200,
                'success' => true,
                'message' => $message,
                'file'    => $file,
                'thumb'   => $thumb,
            ];
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
            return self::mapException($th, ['stage' => 'actionRemoveFile.catch']);
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
            return self::mapException($th, ['stage' => 'actionRemoveFiles.catch']);
        }
    }
}
