<?php
namespace croacworks\essentials\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;
use yii\helpers\FileHelper;
use yii\imagine\Image as ImagineImage;
use Imagine\Image\Box;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use croacworks\essentials\models\File;

class GenerateThumbJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    /** @var string caminho absoluto do arquivo origem (imagem ou vídeo) */
    public string $srcAbs;
    /** @var string caminho absoluto do arquivo da thumb (a ser criado) */
    public string $thumbAbs;
    /** @var int|string 1 (quadrada) ou "LARGURA/ALTURA" (ex.: "300/200") */
    public int|string $thumbAspect = 1;
    /** @var int qualidade JPEG */
    public int $quality = 90;
    /** @var string tipo do arquivo: image|video */
    public string $type = 'image';
    /** @var int|null ID do registro File para atualizar (opcional) */
    public ?int $fileId = null;

    public function execute($queue)
    {
        FileHelper::createDirectory(\dirname($this->thumbAbs));

        if ($this->type === 'video') {
            // extrai frame aos 2s
            $ffmpeg = FFMpeg::create();
            $video  = $ffmpeg->open($this->srcAbs);
            $video->frame(TimeCode::fromSeconds(2))->save($this->thumbAbs);
            // segue o mesmo pipeline de recorte/redimensionamento de imagem abaixo
        }

        if ($this->thumbAspect === 1) {
            $size = @getimagesize($this->thumbAbs === '' ? $this->srcAbs : $this->thumbAbs);
            $src  = $this->thumbAbs !== '' && is_file($this->thumbAbs) ? $this->thumbAbs : $this->srcAbs;

            if (!$size) {
                throw new \RuntimeException('getimagesize falhou para gerar thumbnail.');
            }
            [$w, $h] = $size;
            $side = min($w, $h);
            $x = (int)(($w - $side) / 2);
            $y = (int)(($h - $side) / 2);

            ImagineImage::crop($src, $side, $side, [$x, $y])
                ->save($this->thumbAbs, ['quality' => $this->quality]);

            if ($side > 300) {
                ImagineImage::thumbnail($this->thumbAbs, 300, 300)
                    ->save($this->thumbAbs, ['quality' => $this->quality]);
            }
        } else {
            [$tw, $th] = array_map('intval', explode('/', (string)$this->thumbAspect));
            $src = is_file($this->thumbAbs) ? $this->thumbAbs : $this->srcAbs;

            $size = @getimagesize($src);
            if (!$size) {
                throw new \RuntimeException('getimagesize falhou para gerar thumbnail (aspect).');
            }
            [$w, $h] = $size;

            $targetRatio = $tw / $th;
            $imgRatio = $w / $h;

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

            ImagineImage::crop($src, $newW, $newH, [$x, $y])
                ->resize(new Box($tw, $th))
                ->save($this->thumbAbs, ['quality' => $this->quality]);
        }

        // atualiza DB (opcional)
        if ($this->fileId) {
            /** @var File|null $model */
            $model = File::findOne($this->fileId);
            if ($model) {
                $model->pathThumb = $model->pathThumb ?: $this->toRelative($this->thumbAbs);
                $model->urlThumb  = $model->urlThumb  ?: $this->toPublicUrl($this->thumbAbs);
                $model->save(false, ['pathThumb', 'urlThumb']);
            }
        }
    }

    public function getTtr(): int { return 60; } // tempo máximo por tentativa
    public function canRetry($attempt, $error): bool { return $attempt < 3; }

    private function toRelative(string $abs): string
    {
        $webroot = Yii::getAlias('@webroot');
        return str_replace($webroot, '', $abs);
    }
    private function toPublicUrl(string $abs): string
    {
        $relative = $this->toRelative($abs);
        $web = Yii::getAlias('@web');
        return $web . $relative;
    }
}
