<?php
namespace croacworks\essentials\widgets;

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use croacworks\essentials\assets\StorageAsset;

/**
 * FileUploadInput
 *
 * - Rende um input hidden (ID do arquivo) e um input file para upload imediato (AJAX).
 * - Atualiza preview (thumb/url) após o upload.
 * - Botão "Remover" limpa o campo (opcionalmente deleta no servidor).
 *
 * Opções:
 *  - accept: string (ex. "image/*")
 *  - deleteOnClear: bool (se true, chama /storage/delete ao limpar)
 *  - thumbAspect: int|string (1 ou "LARGURA/ALTURA")
 * 
 * USAGE:
 echo \croacworks\essentials\widgets\FileUploadInput::widget([
    'model' => $model,
    'attribute' => 'file_id',
    'accept' => 'image/*',
    'deleteOnClear' => false, // true = chama /storage/delete ao limpar
    'thumbAspect' => 1,       // ou "300/200"
]);
 */
class FileUploadInput extends InputWidget
{
    public ?string $accept = null;
    public bool $deleteOnClear = false;
    public int|string $thumbAspect = 1;

    public function run(): string
    {
        StorageAsset::register($this->view);

        $idHidden = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
        $nameHidden = $this->name ?? Html::getInputName($this->model, $this->attribute);

        $hidden = Html::activeHiddenInput($this->model, $this->attribute, [
            'id' => $idHidden,
            'class' => 'cw-file-id',
        ]);

        $fileId = $idHidden . '-file';
        $fileInput = Html::fileInput('file', null, [
            'id' => $fileId,
            'class' => 'form-control-file cw-file-input',
            'accept' => $this->accept,
        ]);

        $removeBtn = Html::a('Remover', '#', ['class' => 'btn btn-sm btn-outline-danger cw-btn-remove']);

        $wrapId = $idHidden . '-wrap';
        $acceptAttr = $this->accept ? Html::encode($this->accept) : '';
        $delFlag = $this->deleteOnClear ? '1' : '0';
        $aspect = Html::encode((string)$this->thumbAspect);

        $html = <<<HTML
<div id="{$wrapId}" class="cw-fileupload" data-delete-on-clear="{$delFlag}" data-thumb-aspect="{$aspect}">
  {$hidden}
  <div class="cw-actions">
    <label class="mb-0">Escolher arquivo</label>
    {$fileInput}
    {$removeBtn}
    <button type="button" class="btn btn-sm btn-secondary" data-cw-media-picker data-target-input="{$idHidden}">Escolher da biblioteca</button>
  </div>
  <div class="cw-progress"><div class="cw-progress-bar"></div></div>
  <div class="cw-preview text-muted">Sem arquivo...</div>
</div>
HTML;

        return $html;
    }
}
