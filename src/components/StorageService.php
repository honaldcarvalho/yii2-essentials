<?php
namespace croacworks\essentials\components;

use Yii;
use yii\base\Component;
use yii\db\Transaction;
use yii\helpers\FileHelper;
use yii\imagine\Image as ImagineImage;
use yii\web\UploadedFile;

use croacworks\essentials\components\dto\StorageOptions;
use croacworks\essentials\components\dto\FileDTO;
use croacworks\essentials\components\storage\StorageDriverInterface;

use croacworks\essentials\jobs\TranscodeVideoJob;
use croacworks\essentials\jobs\GenerateThumbJob;
use croacworks\essentials\jobs\VideoProbeDurationJob;

use croacworks\essentials\models\File; // seu AR

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;

/**
 * Serviço de armazenamento de arquivos com pós-processamento (thumb, transcode, duração).
 * 
     'storage' => [
        'class' => \croacworks\essentials\components\StorageService::class,
        'driver' => [
            'class'   => \croacworks\essentials\components\storage\LocalStorageDriver::class,
            'basePath'=> '@webroot/uploads',
            'baseUrl' => '@web/uploads',
        ],
        'defaultThumbSize' => 300,
        'enableQueue'      => true, // usa jobs se existir queue
    ],
    'queue' => [
        'class' => \yii\queue\db\Queue::class,
        'db' => 'db',
        'tableName' => '{{%queue}}',
        'channel' => 'storage',
        'ttr' => 600,
        'attempts' => 2,
    ],
 */

class StorageService extends Component
{
    /** @var StorageDriverInterface|array */
    public $driver;
    public bool $enableQueue = false;
    public int $defaultThumbSize = 300;

    public function init()
    {
        parent::init();
        if (is_array($this->driver)) {
            $this->driver = Yii::createObject($this->driver);
        }
        if (!$this->driver instanceof StorageDriverInterface) {
            throw new \RuntimeException('StorageService: driver inválido.');
        }
    }

    /**
     * Salva o arquivo, pós-processa (thumb/transcode) e opcionalmente persiste no DB.
     * Retorna File (AR) quando $opts->saveModel = true, ou FileDTO quando false.
     * Lança exceção em erros inesperados.
     */
    public function upload(UploadedFile $file, StorageOptions $opts): File|FileDTO
    {
        $created = ['fileAbs' => null, 'thumbAbs' => null];
        $cleanup = function () use (&$created) {
            if ($created['fileAbs']  && is_file($created['fileAbs']))  @unlink($created['fileAbs']);
            if ($created['thumbAbs'] && is_file($created['thumbAbs'])) @unlink($created['thumbAbs']);
        };

        // 1) salvar bruto via driver
        $dto = $this->driver->save($file, $opts);

        // caminhos absolutos
        $absFile = $this->absFromWebPath($dto->path);
        $created['fileAbs'] = $absFile;

        try {
            // 2) pós-processamento por tipo
            if ($dto->type === 'image') {
                // caminho para thumb
                [$thumbRel, $thumbAbs] = $this->pathsForImageThumb($dto);
                if ($this->useQueue()) {
                    Yii::$app->queue->push(new GenerateThumbJob([
                        'srcAbs'      => $absFile,
                        'thumbAbs'    => $thumbAbs,
                        'thumbAspect' => $opts->thumbAspect,
                        'quality'     => 100,
                        'type'        => 'image',
                        'fileId'      => null, // atualizado ao salvar o AR
                    ]));
                } else {
                    $this->generateImageThumbSync($absFile, $thumbAbs, $opts->thumbAspect);
                }
                $dto->pathThumb = $this->relFromAbs($thumbAbs);
                $dto->urlThumb  = $this->publicUrlFromAbs($thumbAbs);
                $created['thumbAbs'] = $thumbAbs;

            } elseif ($dto->type === 'video') {
                // transcode se necessário
                if ($opts->convertVideo && strtolower($dto->extension) !== 'mp4') {
                    if ($this->useQueue()) {
                        Yii::$app->queue->push(new TranscodeVideoJob([
                            'videoAbs' => $absFile,
                            'fileId'   => null,
                        ]));
                    } else {
                        $this->transcodeToMp4Sync($absFile);
                        $dto->extension = 'mp4';
                    }
                }

                // thumb do vídeo
                [$thumbRel, $thumbAbs] = $this->pathsForVideoThumb($dto);
                if ($this->useQueue()) {
                    Yii::$app->queue->push(new GenerateThumbJob([
                        'srcAbs'      => $absFile,
                        'thumbAbs'    => $thumbAbs,
                        'thumbAspect' => 1, // padrão quadrada; mude se quiser respeitar $opts->thumbAspect
                        'quality'     => 100,
                        'type'        => 'video',
                        'fileId'      => null,
                    ]));
                } else {
                    $this->generateVideoThumbSync($absFile, $thumbAbs);
                }
                $dto->pathThumb = $this->relFromAbs($thumbAbs);
                $dto->urlThumb  = $this->publicUrlFromAbs($thumbAbs);
                $created['thumbAbs'] = $thumbAbs;

                // duração
                if ($this->useQueue()) {
                    Yii::$app->queue->push(new VideoProbeDurationJob([
                        'videoAbs' => $absFile,
                        'fileId'   => null,
                    ]));
                } else {
                    $dto->duration = $this->probeDurationSync($absFile);
                }
            }

            // 3) persistência opcional
            if ($opts->saveModel) {
                $tx = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);
                try {
                    $model = new File([
                        'group_id'   => $this->resolveGroupId((int)($opts->groupId ?? 1)),
                        'folder_id'  => $dto->folderId,
                        'name'       => $dto->name,
                        'description'=> $dto->description,
                        'path'       => $dto->path,
                        'url'        => $dto->url,
                        'pathThumb'  => $dto->pathThumb,
                        'urlThumb'   => $dto->urlThumb,
                        'extension'  => $dto->extension,
                        'type'       => $dto->type,
                        'size'       => $dto->size,
                        'duration'   => (int)$dto->duration,
                        'created_at' => $dto->createdAt,
                    ]);

                    if (!$model->save()) {
                        // requisito: se falhar, apagar arquivo e thumb
                        $cleanup();
                        return $model; // retorna o AR com errors() para o controller tratar
                    }

                    // se usamos fila, podemos agora anexar o fileId aos jobs (opcional: enfileirar novamente jobs focados)
                    if ($this->useQueue()) {
                        // thumb (se não existente) e duração (vídeo)
                        if ($dto->type === 'image' || $dto->type === 'video') {
                            Yii::$app->queue->push(new GenerateThumbJob([
                                'srcAbs'      => $absFile,
                                'thumbAbs'    => $created['thumbAbs'] ?? $this->absFromWebPath($dto->pathThumb ?? ''),
                                'thumbAspect' => ($dto->type === 'image') ? $opts->thumbAspect : 1,
                                'quality'     => 100,
                                'type'        => $dto->type,
                                'fileId'      => $model->id,
                            ]));
                        }
                        if ($dto->type === 'video') {
                            Yii::$app->queue->push(new VideoProbeDurationJob([
                                'videoAbs' => $absFile,
                                'fileId'   => $model->id,
                            ]));
                            // se ainda não transcodificou e precisa, garante a fila com id:
                            if ($opts->convertVideo && strtolower($dto->extension) !== 'mp4') {
                                Yii::$app->queue->push(new TranscodeVideoJob([
                                    'videoAbs' => $absFile,
                                    'fileId'   => $model->id,
                                ]));
                            }
                        }
                    }

                    $tx->commit();
                    return $model;

                } catch (\Throwable $e) {
                    $tx->rollBack();
                    $cleanup();
                    Yii::error($e->getMessage(), __METHOD__);
                    throw $e;
                }
            }

            // sem persistir, retorna DTO
            return $dto;

        } catch (\Throwable $e) {
            $cleanup();
            Yii::error("Storage upload error: {$e->getMessage()}", __METHOD__);
            throw $e;
        }
    }

    // ---------------------- Fallbacks síncronos ----------------------

    private function generateImageThumbSync(string $srcAbs, string $thumbAbs, int|string $thumbAspect): void
    {
        FileHelper::createDirectory(\dirname($thumbAbs));

        if ($thumbAspect === 1) {
            $size = @getimagesize($srcAbs);
            if (!$size) throw new \RuntimeException('getimagesize falhou (imagem).');
            [$w, $h] = $size;
            $side = min($w, $h);
            $x = (int)(($w - $side) / 2);
            $y = (int)(($h - $side) / 2);

            ImagineImage::crop($srcAbs, $side, $side, [$x, $y])
                ->save($thumbAbs, ['quality' => 100]);

            if ($side > $this->defaultThumbSize) {
                ImagineImage::thumbnail($thumbAbs, $this->defaultThumbSize, $this->defaultThumbSize)
                    ->save($thumbAbs, ['quality' => 100]);
            }
        } else {
            [$tw, $th] = array_map('intval', explode('/', (string)$thumbAspect));
            $this->createAspectThumb($srcAbs, $thumbAbs, $tw, $th);
        }
    }

    private function generateVideoThumbSync(string $videoAbs, string $thumbAbs): void
    {
        FileHelper::createDirectory(\dirname($thumbAbs));
        $ffmpeg = FFMpeg::create();
        $video  = $ffmpeg->open($videoAbs);
        $video->frame(TimeCode::fromSeconds(2))->save($thumbAbs);

        // recorte quadrado + resize
        $size = @getimagesize($thumbAbs);
        if ($size) {
            [$w, $h] = $size;
            $side = min($w, $h);
            $x = (int)(($w - $side) / 2);
            $y = (int)(($h - $side) / 2);

            ImagineImage::crop($thumbAbs, $side, $side, [$x, $y])
                ->save($thumbAbs, ['quality' => 100]);

            if ($side > $this->defaultThumbSize) {
                ImagineImage::thumbnail($thumbAbs, $this->defaultThumbSize, $this->defaultThumbSize)
                    ->save($thumbAbs, ['quality' => 100]);
            }
        }
    }

    private function transcodeToMp4Sync(string $absFile): void
    {
        $ffmpeg = FFMpeg::create();
        $video  = $ffmpeg->open($absFile);
        $tmpOut = $absFile . '.tmp.mp4';
        $video->save(new X264(), $tmpOut);
        @unlink($absFile);
        rename($tmpOut, $absFile);
    }

    private function probeDurationSync(string $absFile): int
    {
        $ffprobe = \FFMpeg\FFProbe::create();
        return (int)$ffprobe->format($absFile)->get('duration');
    }

    private function createAspectThumb(string $srcAbs, string $destAbs, int $tw, int $th): void
    {
        $size = @getimagesize($srcAbs);
        if (!$size) throw new \RuntimeException('getimagesize falhou.');
        [$w, $h] = $size;

        $targetRatio = $tw / $th;
        $imgRatio    = $w / $h;

        if ($imgRatio > $targetRatio) {
            $newW = (int)($h * $targetRatio);
            $newH = $h;
            $x = (int)(($w - $newW) / 2);
            $y = 0;
        } else {
            $newW = $w;
            $newH = (int)($w / $targetRatio);
            $x = 0;
            $y = (int)(($h - $newH) / 2);
        }

        ImagineImage::crop($srcAbs, $newW, $newH, [$x,$y])
            ->resize(new \Imagine\Image\Box($tw, $th))
            ->save($destAbs, ['quality' => 100]);
    }

    // ---------------------- Helpers de caminho/ACL ----------------------

    private function useQueue(): bool
    {
        return $this->enableQueue && Yii::$app->has('queue');
    }

    /** Converte um web path (ex: /uploads/images/x.jpg) para absoluto */
    private function absFromWebPath(string $webPath): string
    {
        return Yii::getAlias('@webroot') . $webPath;
    }

    /** De absoluto para relativo web (/uploads/...) */
    private function relFromAbs(string $abs): string
    {
        $webroot = Yii::getAlias('@webroot');
        return str_replace($webroot, '', $abs);
    }

    /** De absoluto para URL pública */
    private function publicUrlFromAbs(string $abs): string
    {
        return Yii::getAlias('@web') . $this->relFromAbs($abs);
    }

    private function resolveGroupId(int $fallback): int
    {
        try {
            if (class_exists('\croacworks\essentials\controllers\AuthorizationController')
                && !\croacworks\essentials\controllers\AuthorizationController::isAdmin()) {
                return (int)\croacworks\essentials\controllers\AuthorizationController::userGroup();
            }
        } catch (\Throwable) {}
        return $fallback;
    }

    private function pathsForImageThumb(FileDTO $dto): array
    {
        // ex.: /uploads/images/foo.jpg -> /uploads/images/thumbs/foo.jpg
        $thumbRel = \dirname($dto->path);
        $thumbRel = str_replace('/images', '/images/thumbs', $thumbRel) . '/' . $dto->name;
        $thumbAbs = $this->absFromWebPath($thumbRel);
        FileHelper::createDirectory(\dirname($thumbAbs));
        return [$thumbRel, $thumbAbs];
    }

    private function pathsForVideoThumb(FileDTO $dto): array
    {
        // ex.: /uploads/videos/foo.mp4 -> /uploads/videos/thumbs/foo_mp4.jpg
        $baseDir = str_replace('/videos', '/videos/thumbs', \dirname($dto->path));
        $thumbName = str_replace('.', '_', $dto->name) . '.jpg';
        $thumbRel  = $baseDir . '/' . $thumbName;
        $thumbAbs  = $this->absFromWebPath($thumbRel);
        FileHelper::createDirectory(\dirname($thumbAbs));
        return [$thumbRel, $thumbAbs];
    }
    
    public static function rules(){
        return [
            'GET storage/list'               => 'storage/list',
            'GET storage/info'               => 'storage/info',
            'GET storage/download'           => 'storage/download',
            'POST storage/upload'            => 'storage/upload',
            'POST storage/update'            => 'storage/update',
            'POST storage/delete'            => 'storage/delete',
            'DELETE storage/delete'          => 'storage/delete',
            'POST storage/move'              => 'storage/move',
            'POST storage/replace'           => 'storage/replace',
            'POST storage/attach'            => 'storage/attach',
            'POST storage/detach'            => 'storage/detach',
            'DELETE storage/detach'          => 'storage/detach',
            'POST storage/regenerate-thumb'  => 'storage/regenerate-thumb',
            'POST storage/transcode'         => 'storage/transcode',
            'POST storage/probe-duration'    => 'storage/probe-duration',
        ];
    }
}
