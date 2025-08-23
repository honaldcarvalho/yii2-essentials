<?php
namespace croacworks\essentials\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use croacworks\essentials\assets\StorageAsset;

/**
 * MediaPicker
 * - Botão que abre modal com lista de arquivos da biblioteca (via /storage/list)
 * - Ao clicar num item, preenche o input alvo (ID) informado.
 *
 * Props:
 *  - targetInputId: id do input hidden que receberá o ID do arquivo
 *  - label: texto do botão
 *  - btnOptions: array de opções do botão
 USAGE:
 echo \croacworks\essentials\widgets\MediaPicker::widget([
    'targetInputId' => \yii\helpers\Html::getInputId($model, 'file_id'),
    'label' => 'Selecionar da mídia',
]);
 */
class MediaPicker extends Widget
{
    public string $targetInputId;
    public string $label = 'Escolher da biblioteca';
    public array $btnOptions = ['class' => 'btn btn-secondary btn-sm'];

    public function run(): string
    {
        StorageAsset::register($this->view);

        $options = $this->btnOptions;
        $options['data-cw-media-picker'] = true;
        $options['data-target-input'] = $this->targetInputId;

        return Html::button($this->label, $options);
    }
}
