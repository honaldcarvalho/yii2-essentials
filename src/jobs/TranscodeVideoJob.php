<?php
namespace croacworks\essentials\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use croacworks\essentials\models\File;

class TranscodeVideoJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    /** @var string caminho absoluto do vídeo de entrada (qualquer formato) */
    public string $videoAbs;
    /** @var int|null para atualizar extensão no DB após conversão */
    public ?int $fileId = null;

    public function execute($queue)
    {
        $tmpOut = $this->videoAbs . '.tmp.mp4';

        $ffmpeg = FFMpeg::create();
        $video  = $ffmpeg->open($this->videoAbs);
        $video->save(new X264(), $tmpOut);

        // substitui o arquivo original
        @unlink($this->videoAbs);
        rename($tmpOut, $this->videoAbs);

        if ($this->fileId) {
            $model = File::findOne($this->fileId);
            if ($model) {
                $model->extension = 'mp4';
                $model->save(false, ['extension']);
            }
        }
    }

    public function getTtr(): int { return 600; } // até 10 min por tentativa
    public function canRetry($attempt, $error): bool { return $attempt < 2; }
}
