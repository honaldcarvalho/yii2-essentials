<?php
namespace croacworks\essentials\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class FilePreview extends Widget
{
    public int $fileId;
    public string $emptyText = 'Sem arquivo';

    public function run(): string
    {
        $id = (int)$this->fileId;
        if (!$id) return Html::tag('div', $this->emptyText, ['class' => 'text-muted']);
        $json = @file_get_contents(\Yii::$app->urlManager->createAbsoluteUrl(['/storage/info', 'id' => $id]));
        if (!$json) return Html::tag('div', $this->emptyText, ['class' => 'text-muted']);
        $resp = json_decode($json, true);
        if (!$resp || empty($resp['ok'])) return Html::tag('div', $this->emptyText, ['class' => 'text-muted']);
        $d = $resp['data'];
        $url = $d['urlThumb'] ?: $d['url'];
        if ($d['type']==='image' && $url) {
            return Html::img($url, ['style'=>'max-width:120px;max-height:120px;border-radius:6px']);
        }
        return Html::tag('div', $d['name'] ?? ('#'.$id));
    }
}
