<?php
namespace croacworks\essentials\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;
use FFMpeg\FFProbe;
use croacworks\essentials\models\File;

class VideoProbeDurationJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    /** @var string caminho absoluto do vídeo MP4 (ou outro) */
    public string $videoAbs;
    /** @var int|null ID do registro File para atualizar */
    public ?int $fileId = null;

    public function execute($queue)
    {
        $ffprobe = FFProbe::create();
        $duration = (int)$ffprobe->format($this->videoAbs)->get('duration');

        if ($this->fileId) {
            $model = File::findOne($this->fileId);
            if ($model) {
                $model->duration = $duration;
                $model->save(false, ['duration']);
            }
        }
    }

    public function getTtr(): int { return 60; }
    public function canRetry($attempt, $error): bool { return $attempt < 3; }
}
