<?php

namespace croacworks\essentials\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

use croacworks\essentials\controllers\AuthorizationController as Auth;
use croacworks\essentials\components\StorageService;
use croacworks\essentials\components\dto\StorageOptions;
use croacworks\essentials\jobs\GenerateThumbJob;
use croacworks\essentials\jobs\TranscodeVideoJob;
use croacworks\essentials\jobs\VideoProbeDurationJob;
use croacworks\essentials\models\File; // Ajuste se seu AR tiver outro namespace

class StorageController extends Controller
{
    public $enableCsrfValidation = false; // API-style. Ligue se usar form HTML com CSRF.

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'upload',
                    'delete',
                    'info',
                    'list',
                    'download',
                    'update',
                    'move',
                    'replace',
                    'attach',
                    'detach',
                    'regenerate-thumb',
                    'transcode',
                    'probe-duration'
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // ajuste conforme seu RBAC
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload'           => ['POST'],
                    'delete'           => ['POST', 'DELETE'],
                    'update'           => ['POST', 'PUT', 'PATCH'],
                    'move'             => ['POST'],
                    'replace'          => ['POST'],
                    'attach'           => ['POST'],
                    'detach'           => ['POST', 'DELETE'],
                    'regenerate-thumb' => ['POST'],
                    'transcode'        => ['POST'],
                    'probe-duration'   => ['POST'],
                    'info'             => ['GET'],
                    'list'             => ['GET'],
                    'download'         => ['GET'],
                ],
            ],
        ];
    }

    // ---------------------------- CORE ----------------------------

    /** POST /storage/upload (multipart/form-data) */
    /** POST /storage/upload (multipart/form-data) */
    public function actionUpload(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var StorageService $storage */
        $storage = Yii::$app->storage;

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return $this->asJson(['ok' => false, 'error' => 'Arquivo não encontrado no campo "file".']);
        }

        // Sempre pegar os IDs crus do request (sem confiar neles ainda)
        $requestedGroupId = (int)Yii::$app->request->post('group_id', 0);
        $folderId         = (int)Yii::$app->request->post('folder_id', 1);

        // ======== REGRA DE NEGÓCIO DO GROUP ========
        // Só master pode escolher livremente o group_id. Não-masters: ignorar POST[group_id]
        // e resolver pelo pai (folder) ou pelo próprio usuário.
        $resolvedGroupId = 0;
        try {
            if (class_exists(Auth::class) && method_exists(Auth::class, 'resolveParentGroupId')) {
                // A função já trata: se não master, usa o group do pai/usuário; se master, aceita o informado.
                $resolvedGroupId = (int)Auth::resolveParentGroupId($requestedGroupId, $folderId);
            } else {
                // Fallback mínimo (sem criar helpers novos)
                if (class_exists(Auth::class) && method_exists(Auth::class, 'isMaster') && method_exists(Auth::class, 'userGroup')) {
                    if (Auth::isMaster()) {
                        $resolvedGroupId = $requestedGroupId ?: (int)Auth::userGroup();
                    } else {
                        $resolvedGroupId = (int)Auth::userGroup(); // usuário sempre tem group_id
                    }
                } else {
                    // Último recurso: NÃO deixar 0 (evita "Group is invalid")
                    $resolvedGroupId = (int)(Yii::$app->user->identity->group_id ?? 0);
                }
            }
        } catch (\Throwable $e) {
            // Nunca permitir 0 aqui
            $resolvedGroupId = (int)(Yii::$app->user->identity->group_id ?? 0);
        }

        if ($resolvedGroupId <= 0) {
            return $this->asJson([
                'ok'    => false,
                'error' => 'Falha ao determinar o grupo do arquivo.',
            ]);
        }
        // ============================================

        $opts = new StorageOptions([
            'fileName'     => Yii::$app->request->post('file_name'),
            'description'  => Yii::$app->request->post('description'),
            'folderId'     => $folderId,
            'groupId'      => $resolvedGroupId, // <- FORÇADO
            'saveModel'    => (bool)Yii::$app->request->post('save', 1),
            'convertVideo' => (bool)Yii::$app->request->post('convert_video', 1),
            'thumbAspect'  => Yii::$app->request->post('thumb_aspect', 1), // 1 ou "W/H"
            'quality'      => (int)Yii::$app->request->post('quality', 85),
        ]);

        try {
            $result = $storage->upload($file, $opts);
            if ($result instanceof \yii\db\BaseActiveRecord && $result->hasErrors()) {
                return $this->asJson(['ok' => false, 'errors' => $result->getErrors()]);
            }
            $payload = ($result instanceof \yii\db\BaseActiveRecord) ? $result->attributes : $result->toArray();
            return $this->asJson(['ok' => true, 'data' => $payload]);
        } catch (\Throwable $e) {
            Yii::error("Storage upload failed: {$e->getMessage()}", __METHOD__);
            return $this->asJson(['ok' => false, 'error' => 'Falha ao processar upload.', 'detail' => YII_ENV_DEV ? $e->getMessage() : null]);
        }
    }

    /** GET /storage/info?id=123 */
    public function actionInfo(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = $this->findFile($id);
        return $this->asJson(['ok' => true, 'data' => $file->attributes]);
    }

    /** GET /storage/list?folder_id=&type=&q=&page=&pageSize= */
    public function actionList(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = File::find();

        // filtro por grupo (se não admin, força group_id do usuário)
        $groupId = (int)Yii::$app->request->get('group_id', 0);
        $query = $this->applyGroupFilter($query, $groupId);

        // demais filtros
        if ($folderId = Yii::$app->request->get('folder_id')) {
            $query->andWhere(['folder_id' => (int)$folderId]);
        }
        if ($type = Yii::$app->request->get('type')) {
            $query->andWhere(['type' => $type]); // 'image'|'video'|'doc'
        }
        if ($q = Yii::$app->request->get('q')) {
            $query->andFilterWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'description', $q],
                ['like', 'extension', $q],
            ]);
        }

        $page     = max(1, (int)Yii::$app->request->get('page', 1));
        $pageSize = min(100, max(1, (int)Yii::$app->request->get('pageSize', 20)));
        $count    = (clone $query)->count();
        $items    = $query->orderBy(['id' => SORT_DESC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->asArray()
            ->all();

        return $this->asJson([
            'ok' => true,
            'meta' => ['page' => $page, 'pageSize' => $pageSize, 'total' => (int)$count],
            'data' => $items,
        ]);
    }

    /** GET /storage/download?id=123 */
    public function actionDownload(int $id)
    {
        $file = $this->findFile($id);
        $abs  = $this->absFromWebPath($file->path);
        if (!is_file($abs)) {
            throw new \yii\web\NotFoundHttpException('Arquivo não encontrado no disco.');
        }
        return Yii::$app->response->sendFile($abs, $file->name);
    }

    /** POST|PUT|PATCH /storage/update?id=123  (name, description) */
    public function actionUpdate(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findFile($id);

        $name = Yii::$app->request->post('name');
        $description = Yii::$app->request->post('description');

        if ($name !== null && $name !== '' && $name !== $model->name) {
            // renomear arquivo físico mantendo extensão
            $oldAbs = $this->absFromWebPath($model->path);
            $newName = $name;
            // garante extensão
            $ext = pathinfo($oldAbs, PATHINFO_EXTENSION);
            if (!str_ends_with($newName, ".{$ext}")) {
                $newName .= ".{$ext}";
            }
            $newRel = \dirname($model->path) . '/' . $newName;
            $newAbs = $this->absFromWebPath($newRel);

            FileHelper::createDirectory(\dirname($newAbs));
            if (!@rename($oldAbs, $newAbs)) {
                return $this->asJson(['ok' => false, 'error' => 'Falha ao renomear arquivo físico.']);
            }

            // renomeia thumb se existir
            if ($model->pathThumb) {
                $oldThumbAbs = $this->absFromWebPath($model->pathThumb);
                if (is_file($oldThumbAbs)) {
                    $thumbDir = \dirname($model->pathThumb);
                    // mantém o padrão de nome da thumb
                    if ($model->type === 'video') {
                        $newThumbName = str_replace('.', '_', $newName) . '.jpg';
                    } else {
                        $newThumbName = $newName;
                    }
                    $newThumbRel = $thumbDir . '/' . $newThumbName;
                    $newThumbAbs = $this->absFromWebPath($newThumbRel);
                    @rename($oldThumbAbs, $newThumbAbs);
                    $model->pathThumb = $newThumbRel;
                    $model->urlThumb  = $this->publicUrlFromAbs($newThumbAbs);
                }
            }

            $model->name = $newName;
            $model->path = $newRel;
            $model->url  = $this->publicUrlFromAbs($newAbs);
        }

        if ($description !== null) {
            $model->description = $description;
        }

        if (!$model->save()) {
            return $this->asJson(['ok' => false, 'errors' => $model->getErrors()]);
        }

        return $this->asJson(['ok' => true, 'data' => $model->attributes]);
    }

    /** POST|DELETE /storage/delete?id=123 */
    public function actionDelete(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findFile($id);

        $fileAbs  = $this->absFromWebPath($model->path);
        $thumbAbs = $model->pathThumb ? $this->absFromWebPath($model->pathThumb) : null;

        $okDb = $model->delete() !== false;

        // limpa disco independente do resultado do delete do DB
        $okFs  = true;
        if (is_file($fileAbs)) {
            $okFs = $okFs && @unlink($fileAbs);
        }
        if ($thumbAbs && is_file($thumbAbs)) {
            $okFs = $okFs && @unlink($thumbAbs);
        }

        return $this->asJson(['ok' => (bool)($okDb && $okFs)]);
    }

    /** POST /storage/move?id=123  body: folder_id */
    public function actionMove(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findFile($id);
        $folderId = (int)Yii::$app->request->post('folder_id');

        if (!$folderId) {
            return $this->asJson(['ok' => false, 'error' => 'folder_id é obrigatório.']);
        }

        $model->folder_id = $folderId;
        if (!$model->save(false, ['folder_id'])) {
            return $this->asJson(['ok' => false, 'error' => 'Não foi possível mover o arquivo.']);
        }
        return $this->asJson(['ok' => true, 'data' => $model->attributes]);
    }

    /**
     * POST /storage/replace?id=123 (multipart/form-data com "file")
     * Substitui o conteúdo do arquivo mantendo o mesmo registro.
     */
    public function actionReplace(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findFile($id);

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return $this->asJson(['ok' => false, 'error' => 'Arquivo não encontrado (file).']);
        }

        $abs = $this->absFromWebPath($model->path);
        $oldAbs = $abs . '.bak';
        // backup simples
        @rename($abs, $oldAbs);

        if (!$file->saveAs($abs)) {
            // rollback
            @rename($oldAbs, $abs);
            return $this->asJson(['ok' => false, 'error' => 'Falha ao substituir arquivo.']);
        }
        // remove backup
        @unlink($oldAbs);

        $model->size = filesize($abs) ?: $model->size;
        // reseta dados dependentes
        if ($model->type === 'image' && $model->pathThumb) {
            // thumb ficou desatualizada, recomenda regenerar
        }
        if ($model->type === 'video') {
            // duração e thumb podem mudar — oferece endpoints para regenerar
        }

        $model->updated_at = time();
        $model->save(false, ['size', 'updated_at']);

        return $this->asJson(['ok' => true, 'data' => $model->attributes]);
    }

    // ---------------------------- ANEXOS ----------------------------

    /**
     * POST /storage/attach
     * body: class_name, file_id, model_id, field_model_id="...", field_file_id="..."
     * Cria uma linha em uma tabela pivô (se existir). Mantido genérico.
     */
    public function actionAttach(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $className = Yii::$app->request->post('class_name'); // ex.: \common\models\PostFile
        $fileId    = (int)Yii::$app->request->post('file_id');
        $modelId   = (int)Yii::$app->request->post('model_id');
        $fieldModelId = Yii::$app->request->post('field_model_id', 'model_id');
        $fieldFileId  = Yii::$app->request->post('field_file_id', 'file_id');

        if (!$className || !$fileId || !$modelId) {
            return $this->asJson(['ok' => false, 'error' => 'Parâmetros obrigatórios ausentes.']);
        }
        if (!class_exists($className)) {
            return $this->asJson(['ok' => false, 'error' => 'Classe pivô não encontrada.']);
        }

        $pivot = new $className([
            $fieldModelId => $modelId,
            $fieldFileId  => $fileId,
        ]);

        if (!$pivot->save()) {
            return $this->asJson(['ok' => false, 'errors' => $pivot->getErrors()]);
        }

        return $this->asJson(['ok' => true, 'data' => $pivot->attributes]);
    }

    /**
     * POST|DELETE /storage/detach
     * body: class_name, file_id, model_id, field_model_id="...", field_file_id="..."
     */
    public function actionDetach(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $className = Yii::$app->request->post('class_name');
        $fileId    = (int)Yii::$app->request->post('file_id');
        $modelId   = (int)Yii::$app->request->post('model_id');
        $fieldModelId = Yii::$app->request->post('field_model_id', 'model_id');
        $fieldFileId  = Yii::$app->request->post('field_file_id', 'file_id');

        if (!$className || !$fileId || !$modelId) {
            return $this->asJson(['ok' => false, 'error' => 'Parâmetros obrigatórios ausentes.']);
        }
        if (!class_exists($className)) {
            return $this->asJson(['ok' => false, 'error' => 'Classe pivô não encontrada.']);
        }

        $rows = $className::deleteAll([$fieldModelId => $modelId, $fieldFileId => $fileId]);
        return $this->asJson(['ok' => true, 'deleted' => (int)$rows]);
    }

    // ---------------------------- TAREFAS / PROCESSAMENTO ----------------------------

    /** POST /storage/regenerate-thumb?id=123&aspect=1|W/H  */
    public function actionRegenerateThumb(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var StorageService $storage */
        $storage = Yii::$app->storage;

        $model = $this->findFile($id);
        $aspect = Yii::$app->request->get('aspect', 1);

        if ($model->type !== 'image' && $model->type !== 'video') {
            return $this->asJson(['ok' => false, 'error' => 'Arquivo não é imagem ou vídeo.']);
        }

        // calcula paths
        if ($model->type === 'image') {
            $thumbRel = str_replace('/images/', '/images/thumbs/', $model->path);
            $thumbAbs = $this->absFromWebPath($thumbRel);
            $srcAbs   = $this->absFromWebPath($model->path);
            FileHelper::createDirectory(\dirname($thumbAbs));

            if ($storage->enableQueue && Yii::$app->has('queue')) {
                Yii::$app->queue->push(new GenerateThumbJob([
                    'srcAbs'      => $srcAbs,
                    'thumbAbs'    => $thumbAbs,
                    'thumbAspect' => $aspect,
                    'quality'     => 100,
                    'type'        => 'image',
                    'fileId'      => $model->id,
                ]));
                return $this->asJson(['ok' => true, 'queued' => true]);
            }

            // síncrono
            $this->generateImageThumbNow($srcAbs, $thumbAbs, $aspect);
            $model->pathThumb = $thumbRel;
            $model->urlThumb  = $this->publicUrlFromAbs($thumbAbs);
            $model->save(false, ['pathThumb', 'urlThumb']);
            return $this->asJson(['ok' => true, 'queued' => false, 'data' => $model->attributes]);
        } else { // video
            $baseDir  = str_replace('/videos', '/videos/thumbs', \dirname($model->path));
            $thumbRel = $baseDir . '/' . str_replace('.', '_', $model->name) . '.jpg';
            $thumbAbs = $this->absFromWebPath($thumbRel);
            $srcAbs   = $this->absFromWebPath($model->path);
            FileHelper::createDirectory(\dirname($thumbAbs));

            if ($storage->enableQueue && Yii::$app->has('queue')) {
                Yii::$app->queue->push(new GenerateThumbJob([
                    'srcAbs'      => $srcAbs,
                    'thumbAbs'    => $thumbAbs,
                    'thumbAspect' => 1,
                    'quality'     => 100,
                    'type'        => 'video',
                    'fileId'      => $model->id,
                ]));
                return $this->asJson(['ok' => true, 'queued' => true]);
            }

            // síncrono
            $this->generateVideoThumbNow($srcAbs, $thumbAbs);
            $model->pathThumb = $thumbRel;
            $model->urlThumb  = $this->publicUrlFromAbs($thumbAbs);
            $model->save(false, ['pathThumb', 'urlThumb']);
            return $this->asJson(['ok' => true, 'queued' => false, 'data' => $model->attributes]);
        }
    }

    /** POST /storage/transcode?id=123  (converte para mp4 in-place) */
    public function actionTranscode(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var StorageService $storage */
        $storage = Yii::$app->storage;

        $model = $this->findFile($id);
        if ($model->type !== 'video') {
            return $this->asJson(['ok' => false, 'error' => 'Arquivo não é vídeo.']);
        }
        if (strtolower($model->extension) === 'mp4') {
            return $this->asJson(['ok' => true, 'message' => 'Já está em MP4.']);
        }

        $videoAbs = $this->absFromWebPath($model->path);

        if ($storage->enableQueue && Yii::$app->has('queue')) {
            Yii::$app->queue->push(new TranscodeVideoJob([
                'videoAbs' => $videoAbs,
                'fileId'   => $model->id,
            ]));
            return $this->asJson(['ok' => true, 'queued' => true]);
        }

        // síncrono
        $this->transcodeToMp4Now($videoAbs);
        $model->extension = 'mp4';
        $model->save(false, ['extension']);
        return $this->asJson(['ok' => true, 'queued' => false]);
    }

    /** POST /storage/probe-duration?id=123  (mede duração e salva) */
    public function actionProbeDuration(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var StorageService $storage */
        $storage = Yii::$app->storage;

        $model = $this->findFile($id);
        if ($model->type !== 'video') {
            return $this->asJson(['ok' => false, 'error' => 'Arquivo não é vídeo.']);
        }

        $videoAbs = $this->absFromWebPath($model->path);

        if ($storage->enableQueue && Yii::$app->has('queue')) {
            Yii::$app->queue->push(new VideoProbeDurationJob([
                'videoAbs' => $videoAbs,
                'fileId'   => $model->id,
            ]));
            return $this->asJson(['ok' => true, 'queued' => true]);
        }

        // síncrono
        $duration = $this->probeDurationNow($videoAbs);
        $model->duration = (int)$duration;
        $model->save(false, ['duration']);

        return $this->asJson(['ok' => true, 'queued' => false, 'duration' => (int)$duration]);
    }

    // ---------------------------- HELPERS ----------------------------

    private function findFile(int $id): File
    {
        $q = File::find()->where(['id' => $id]);
        $q = $this->applyGroupFilter($q);
        $m = $q->one();
        if (!$m) {
            throw new \yii\web\NotFoundHttpException('Arquivo não encontrado.');
        }
        return $m;
    }

    private function applyGroupFilter($query, ?int $requestedGroupId = null)
    {
        try {
            if (class_exists(Auth::class) && method_exists(Auth::class, 'isMaster') && method_exists(Auth::class, 'userGroup')) {
                if (Auth::isMaster()) {
                    // Master pode filtrar por qualquer group_id explicitamente solicitado
                    if ($requestedGroupId !== null && $requestedGroupId > 0) {
                        $query->andWhere(['group_id' => (int)$requestedGroupId]);
                    }
                } else {
                    // Não-master sempre restringe ao seu group (ou herdado) — nunca deixar 0
                    $gid = (int)Auth::userGroup();
                    if ($gid > 0) {
                        $query->andWhere(['group_id' => $gid]);
                    } else {
                        // Segurança extra: se por algum motivo não vier, bloqueia resultados
                        $query->andWhere('1=0');
                    }
                }
                return $query;
            }
        } catch (\Throwable) {
            // cair no fallback abaixo
        }

        // Fallback bem conservador (sem helpers novos)
        $gid = (int)(Yii::$app->user->identity->group_id ?? 0);
        if ($gid > 0) {
            $query->andWhere(['group_id' => $gid]);
        } else {
            $query->andWhere('1=0');
        }
        return $query;
    }


    private function absFromWebPath(string $webPath): string
    {
        return Yii::getAlias('@webroot') . $webPath;
    }

    private function publicUrlFromAbs(string $abs): string
    {
        return Yii::getAlias('@web') . str_replace(Yii::getAlias('@webroot'), '', $abs);
    }

    // Síncronos locais (fallbacks):
    private function generateImageThumbNow(string $srcAbs, string $thumbAbs, int|string $aspect): void
    {
        FileHelper::createDirectory(\dirname($thumbAbs));
        if ($aspect === 1) {
            $size = @getimagesize($srcAbs);
            if (!$size) {
                throw new \RuntimeException('getimagesize falhou.');
            }
            [$w, $h] = $size;
            $side = min($w, $h);
            $x = (int)(($w - $side) / 2);
            $y = (int)(($h - $side) / 2);
        } else {
            [$tw, $th] = array_map('intval', explode('/', (string)$aspect));
            $size = @getimagesize($srcAbs);
            if (!$size) {
                throw new \RuntimeException('getimagesize falhou.');
            }
            [$w, $h] = $size;
            $target = $tw / $th;
            $ratio = $w / $h;
            if ($ratio > $target) {
                $newW = (int)($h * $target);
                $newH = $h;
                $x = (int)(($w - $newW) / 2);
                $y = 0;
            } else {
                $newW = $w;
                $newH = (int)($w / $target);
                $x = 0;
                $y = (int)(($h - $newH) / 2);
            }
            \yii\imagine\Image::crop($srcAbs, $newW, $newH, [$x, $y])
                ->resize(new \Imagine\Image\Box($tw, $th))
                ->save($thumbAbs, ['quality' => 100]);
            return;
        }
        \yii\imagine\Image::crop($srcAbs, $side, $side, [$x, $y])->save($thumbAbs, ['quality' => 100]);
        if ($side > 300) {
            \yii\imagine\Image::thumbnail($thumbAbs, 300, 300)->save($thumbAbs, ['quality' => 100]);
        }
    }

    private function generateVideoThumbNow(string $videoAbs, string $thumbAbs): void
    {
        FileHelper::createDirectory(\dirname($thumbAbs));
        $ffmpeg = \FFMpeg\FFMpeg::create();
        $video  = $ffmpeg->open($videoAbs);
        $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))->save($thumbAbs);
        // crop quadrado + resize
        $size = @getimagesize($thumbAbs);
        if ($size) {
            [$w, $h] = $size;
            $side = min($w, $h);
            $x = (int)(($w - $side) / 2);
            $y = (int)(($h - $side) / 2);
            \yii\imagine\Image::crop($thumbAbs, $side, $side, [$x, $y])->save($thumbAbs, ['quality' => 100]);
            if ($side > 300) {
                \yii\imagine\Image::thumbnail($thumbAbs, 300, 300)->save($thumbAbs, ['quality' => 100]);
            }
        }
    }

    private function transcodeToMp4Now(string $abs): void
    {
        $ffmpeg = \FFMpeg\FFMpeg::create();
        $video  = $ffmpeg->open($abs);
        $tmpOut = $abs . '.tmp.mp4';
        $video->save(new \FFMpeg\Format\Video\X264(), $tmpOut);
        @unlink($abs);
        rename($tmpOut, $abs);
    }

    private function probeDurationNow(string $abs): int
    {
        $ff = \FFMpeg\FFProbe::create();
        return (int)$ff->format($abs)->get('duration');
    }
}
