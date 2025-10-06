<?php
namespace croacworks\essentials\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * DocumentUploadInstant
 * ---------------------
 *
 * A widget similar to UploadImageInstant, designed for document uploads (PDF/DOCX/TXT).
 * It uploads the file instantly via AJAX to `/storage/upload`, updates the model’s `file_id`,
 * and displays the uploaded file name and size.
 *
 * Example:
 * ```php
 * <?= DocumentUploadInstant::widget([
 *     'model' => $model,
 *     'attribute' => 'file_id',
 * ]) ?>
 * ```
 */
class DocumentUploadInstant extends Widget
{
    /** @var \yii\db\ActiveRecord The model instance */
    public $model;

    /** @var string The attribute name (usually 'file_id') */
    public $attribute;

    /** @var string Accepted file extensions */
    public $accept = '.pdf,.doc,.docx,.txt';

    /** @var array|string REST endpoint for instant upload */
    public $sendUrl = ['/rest/storage/send'];


    public function run(): string
    {
        $id    = Html::getInputId($this->model, $this->attribute);
        $name  = Html::getInputName($this->model, $this->attribute);
        $value = Html::getAttributeValue($this->model, $this->attribute);

        // File input
        $inputFile = Html::fileInput(null, null, [
            'id'     => $id . '-input',
            'accept' => $this->accept,
            'class'  => 'form-control',
        ]);

        // Hidden field that stores file_id
        $inputHidden = Html::hiddenInput($name, $value, ['id' => $id]);

        // Info section (shows current or uploaded file)
        $fileInfo = Html::tag(
            'div',
            $value
                ? Yii::t('app', 'File already attached (ID: {id})', ['id' => $value])
                : Yii::t('app', 'No file uploaded yet'),
            ['id' => $id . '-info', 'class' => 'mt-2 text-muted small']
        );

        // JavaScript: handle upload instantly
        $js = <<<JS
const inputFile = document.getElementById('$id-input');
inputFile.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const info = document.getElementById('$id-info');
    info.innerText = Yii.t('app', 'Uploading...');

    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch('{$this->sendUrl}', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success && json.file_id) {
            document.getElementById('$id').value = json.file_id;
            info.innerText = `✅ \${file.name} (\${(file.size / 1024).toFixed(1)} KB)`;

            Swal.fire({
                icon: 'success',
                title: Yii.t('app', 'Upload completed'),
                text: file.name,
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            throw new Error(json.message || Yii.t('app', 'Upload failed'));
        }
    } catch (err) {
        info.innerText = '❌ ' + Yii.t('app', 'Upload error');
        Swal.fire(
            Yii.t('app', 'Error'),
            err.message,
            'error'
        );
    }
});
JS;

        $this->view->registerJs($js, View::POS_END);

        return Html::tag('div', $inputFile . $inputHidden . $fileInfo, [
            'class' => 'document-upload-instant',
        ]);
    }
}
