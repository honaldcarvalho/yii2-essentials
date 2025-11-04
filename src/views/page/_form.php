<?php

use croacworks\essentials\models\Language;
use croacworks\essentials\models\PageSection;
use croacworks\essentials\models\Tag;
use yii\helpers\Html;
use croacworks\essentials\widgets\form\ActiveForm;
use croacworks\essentials\widgets\form\TinyMCE;
use croacworks\essentials\widgets\UploadImageInstant;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\Page $model */
/** @var yii\widgets\ActiveForm $form */

$initTags  = [];
$suggestUrl = Url::to(['/tag/suggest']);
$searchUrl  = Url::to(['/tag/search']);

if (!$model->isNewRecord && !empty($model->tagIds)) {
    $initTags = Tag::find()
        ->select(['name', 'id'])
        ->where(['id' => (array)$model->tagIds])
        ->indexBy('id')
        ->column(); // [id => name]
}

$inputId = Html::getInputId($model, 'tagIds');

$this->registerJs(<<<JS
// TinyMCE helpers
function setFieldText(fieldId, text) {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.get(fieldId)) {
    tinyMCE.get(fieldId).setContent(text || '');
    return;
  }
  const el = document.getElementById(fieldId);
  if (el) el.value = text || '';
}
function getFieldText(fieldId) {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.get(fieldId)) {
    return tinyMCE.get(fieldId).getContent({ format: 'raw' }) || '';
  }
  const el = document.getElementById(fieldId);
  if (!el) return '';
  return (el.value ?? el.textContent ?? '').trim();
}
function getTextValue(id) {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.get(id)) {
    return tinyMCE.get(id).getContent({ format: 'text' }).trim();
  }
  const node = document.getElementById(id);
  return node ? (node.value || '').trim() : '';
}

// Clone hint on language change
const langSelect = document.getElementById('page-language_id');
const originalLang = langSelect.getAttribute('data-original') || langSelect.value;

function showCloneAlert() {
  const current = langSelect.value;
  const isLanguageClone = current && originalLang && current !== originalLang;
  if (isLanguageClone) {
    Swal.fire({
      icon: 'info',
      title: yii.t('app', 'Language Clone'),
      text: yii.t('app', 'You changed the language. The system will create a translation within the same group (language clone).'),
      toast: true,
      position: 'bottom-end',
      timer: 6000,
      showConfirmButton: false
    });
  } else {
    Swal.fire({
      icon: 'warning',
      title: yii.t('app', 'Full Clone'),
      text: yii.t('app', 'Same language detected. A new independent group will be created (full clone).'),
      toast: true,
      position: 'bottom-end',
      timer: 6000,
      showConfirmButton: false
    });
  }
}

langSelect.addEventListener('change', async function () {
  showCloneAlert();

  const selectedOption = this.options[this.selectedIndex];
  const targetCode = selectedOption?.dataset?.code || null;

  if (!targetCode) {
    Swal.fire({
      icon: 'warning',
      title: yii.t('app', 'Invalid language'),
      text: yii.t('app', 'Could not determine the selected language code.')
    });
    return;
  }

  // Minimal language list for the dialog
  const languages = [
    { code: 'pt', name: 'Portuguese' },
    { code: 'en', name: 'English' },
    { code: 'es', name: 'Spanish' }
  ];

  const targetOptions = languages.map(lang => `<option value="\${lang.code}">\${lang.name}</option>`).join('');
  const sourceOptions = `<option value="auto">\${yii.t('app', 'Auto-detect')}</option>` + targetOptions;

  const result = await Swal.fire({
    title: yii.t('app', 'Auto-translate?'),
    html: `
      <div class="text-start">
        <p>\${yii.t('app', 'Translate page text fields to')} <b>\${selectedOption.text}</b>?</p>
        <p class="mt-3">\${yii.t('app', 'Source language (optional, use "auto" to detect):')}</p>
        <select id="swal-source-lang" class="swal2-select">\${sourceOptions}</select>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: yii.t('app', 'Yes, translate'),
    cancelButtonText: yii.t('app', 'No'),
    preConfirm: () => {
      const source = document.getElementById('swal-source-lang').value.trim() || 'auto';
      return { source };
    }
  });

  if (!result.isConfirmed) return;
  const { source } = result.value;

  const fields = [
    { id: 'page-title', label: yii.t('app', 'Title') },
    { id: 'page-description', label: yii.t('app', 'Description') },
    { id: 'page-content', label: yii.t('app', 'Content') }
  ];

  const toTranslate = fields
    .map(f => ({ ...f, text: getFieldText(f.id) }))
    .filter(f => f.text && f.text.length > 0);

  if (!toTranslate.length) {
    Swal.fire({
      icon: 'info',
      title: yii.t('app', 'Nothing to translate'),
      text: yii.t('app', 'Fill title, description or content first.')
    });
    return;
  }

  Swal.fire({
    title: yii.t('app', 'Translating...'),
    html: `<div id="translation-status" class="text-start"></div>`,
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });

  const statusDiv = () => document.getElementById('translation-status');
  const appendStatus = (msg) => {
    if (statusDiv()) {
      statusDiv().innerHTML += `<div>\${msg}</div>`;
      statusDiv().scrollTop = statusDiv().scrollHeight;
    }
  };

  for (const f of toTranslate) {
    appendStatus(`🔄 \${yii.t('app', 'Translating')} \${f.label.toLowerCase()}...`);
    try {
      const res = await fetch(`/page/suggest-translation?language=\${encodeURIComponent(targetCode)}&to=\${encodeURIComponent(source)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: f.text })
      });
      const json = await res.json();
      if (json && json.success && json.translation) {
        setFieldText(f.id, json.translation);
        appendStatus(`✅ \${f.label} \${yii.t('app', 'translated successfully!')}`);
      } else {
        appendStatus(`⚠️ \${yii.t('app', 'Failed to translate')} \${f.label.toLowerCase()}`);
      }
    } catch (e) {
      appendStatus(`❌ \${yii.t('app', 'Error translating')} \${f.label.toLowerCase()}`);
      console.error('Translation error:', f.id, e);
    }
  }

  appendStatus(`<hr><b>✅ \${yii.t('app', 'Translation finished!')}</b>`);
  setTimeout(() => Swal.close(), 2000);
});

// Select2 (AJAX search + free tagging)
var el = $('#page-tagids');
el.select2({
  width: '100%',
  placeholder: yii.t('app', 'Select or type tags...'),
  tags: true,
  tokenSeparators: [','],
  minimumInputLength: 1,
  ajax: {
    url: '/tag/search',
    dataType: 'json',
    delay: 250,
    data: function (params) { return { q: params.term }; },
    processResults: function (data) { return { results: data }; }
  },
  createTag: function (params) {
    var term = (params.term || '').trim();
    if (term === '') return null;
    return { id: term, text: term, newTag: true };
  },
  templateSelection: function (item) { return item.text || item.id; },
  escapeMarkup: function (m) { return m; }
});

// Add a tag into <select multiple>
function addTagToSelect(selectId, text, value) {
  var select = document.getElementById(selectId);
  if (!select) return;
  var val = value || text;
  var exists = Array.from(select.options).some(function (o) {
    return String(o.value) === String(val);
  });
  if (!exists) {
    var opt = new Option(text, val, true, true);
    select.add(opt);
    $(select).trigger('change');
  }
}

// Fetch tag suggestions and render buttons
function fetchTagSuggestions(url, selectId, fields) {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.triggerSave) {
    tinyMCE.triggerSave();
  }

  var payload = {};
  fields.forEach(function (f) {
    payload[f.name] = getTextValue(f.id);
  });

  if (!payload.title && !payload.description && !payload.content) {
    Swal.fire({
      icon: 'info',
      title: yii.t('app', 'Nothing to suggest'),
      text: yii.t('app', 'Fill title, description or content first.')
    });
    return;
  }

  payload.seed = Math.floor(Math.random() * 999999);
  payload.language_id = document.getElementById('page-language_id')?.value || '';

  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(payload)
  })
    .then(r => r.json())
    .then(json => {
      var list = (json && json.suggestions) ? json.suggestions : [];
      var wrap = document.getElementById('tag-suggestions');

      if (!wrap.querySelector('.suggestion-container')) {
        wrap.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>\${yii.t('app', 'Tag suggestions')}</strong>
            <button id="btn-clear-suggestions" type="button" class="btn btn-sm btn-outline-danger">\${yii.t('app', 'Clear')}</button>
          </div>
          <div class="suggestion-container"></div>
        `;
        wrap.querySelector('#btn-clear-suggestions').addEventListener('click', function () {
          wrap.innerHTML = '';
        });
      }

      var container = wrap.querySelector('.suggestion-container');

      if (!list.length) {
        if (!container.innerHTML.trim()) {
          container.innerHTML = '<small class="text-muted">' + yii.t('app', 'No suggestions found') + '</small>';
        }
        return;
      }

      list.forEach(sug => {
        var existsBtn = Array.from(container.querySelectorAll('button')).some(btn =>
          btn.textContent.trim().toLowerCase() === String(sug.text || '').toLowerCase()
        );
        if (existsBtn) return;

        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm me-1 mb-1 ' + (sug.exists ? 'btn-outline-primary' : 'btn-outline-success');
        b.textContent = sug.text;

        b.addEventListener('click', function () {
          addTagToSelect(selectId, sug.text, sug.id || null);
          b.remove();
          if (!container.querySelector('button')) {
            wrap.innerHTML = '';
          }
        });

        container.appendChild(b);
      });
    })
    .catch(err => {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: yii.t('app', 'Error'),
        text: yii.t('app', 'Failed to generate suggestions.')
      });
    });
}

// Auto-summarize description
document.getElementById('btn-auto-summary').addEventListener('click', async function () {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.triggerSave) tinyMCE.triggerSave();

  const title = getTextValue('page-title');
  const description = getTextValue('page-description');
  const content = getTextValue('page-content');
  const langId = document.getElementById('page-language_id')?.value || '';

  if (!title && !description && !content) {
    Swal.fire({
      icon: 'info',
      title: yii.t('app', 'Nothing to summarize'),
      text: yii.t('app', 'Fill title, description or content first.')
    });
    return;
  }

  Swal.fire({
    title: yii.t('app', 'Generating summary...'),
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  const res = await fetch('/tag/summarize', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ title, description, content, language_id: langId })
  });
  const json = await res.json();

  Swal.close();

  if (json.success) {
    setFieldText('page-description', json.summary);
    Swal.fire({
      icon: 'success',
      title: yii.t('app', 'Summary generated!'),
      text: yii.t('app', 'The description field was filled automatically.'),
      toast: true,
      position: 'bottom-end',
      timer: 4000,
      showConfirmButton: false
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: yii.t('app', 'Failed to generate summary'),
      text: json.message || yii.t('app', 'An error occurred while generating the description.')
    });
  }
});

// Suggest tags (button)
document.getElementById('btn-suggest-tags').addEventListener('click', function () {
  fetchTagSuggestions(
    '/tag/suggest',
    'page-tagids',
    [
      { id: 'page-title',       name: 'title' },
      { id: 'page-description', name: 'description' },
      { id: 'page-content',     name: 'content' }
    ]
  );
});
JS);

?>

<div class="page-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'file_id')
        ->fileInput([
            'id' => \yii\helpers\Html::getInputId($model, 'file_id'),
            'accept' => 'image/*',
            'style' => 'display:none'
        ])->label(false) ?>

    <?= UploadImageInstant::widget([
        'mode'        => 'defer', // upload on submit
        'model'       => $model,
        'attribute'   => 'file_id',
        'fileInputId' => \yii\helpers\Html::getInputId($model, 'file_id'),
        'imageUrl'    => $model->file->url ?? '',
        'aspectRatio' => '4/3',
    ]) ?>

    <div class="row mb-3">
        <div class="col-sm-6">
            <?= $form->field($model, 'page_section_id')->dropDownList(
                yii\helpers\ArrayHelper::map(PageSection::find()->all(), 'id', 'name'),
                ['prompt' => Yii::t('app', '-- select a section --')]
            ) ?>
        </div>

        <div class="col-sm-6">
            <?= $form->field($model, 'language_id')->dropDownList(
                yii\helpers\ArrayHelper::map(Language::find()->all(), 'id', 'name'),
                [
                    'prompt'  => Yii::t('app', 'Select Language'),
                    'options' => \yii\helpers\ArrayHelper::map(
                        Language::find()->all(),
                        'id',
                        fn($lang) => ['data-code' => $lang->code]
                    ),
                ]
            ) ?>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-3">
            <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-9">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-12">
            <?= $form->field($model, 'description')->textarea(['rows' => 2]) ?>
            <button type="button" id="btn-auto-summary" class="btn btn-outline-secondary btn-sm">
                <?= Yii::t('app', 'Generate automatic description') ?>
            </button>
        </div>
    </div>

    <?= $form->field($model, 'content')->widget(TinyMCE::class, [
        'options' => ['rows' => 20]
    ]); ?>

    <?= $form->field($model, 'custom_css')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'custom_js')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'keywords')->textarea(['rows' => 6]) ?>

    <div class="row mb-3">
        <div class="col-sm-12 mb-3">
            <?= $form->field($model, 'tagIds')->dropDownList(
                $initTags,
                [
                    'id'       => $inputId,
                    'multiple' => true,
                    'class'    => 'form-control select2-plain',
                ]
            )->label(Yii::t('app', 'Tags')) ?>

            <button type="button" id="btn-suggest-tags" class="btn btn-outline-secondary btn-sm">
                <?= Yii::t('app', 'Suggest tags') ?>
            </button>
            <div id="tag-suggestions" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    </div>

    <?= $form->field($model, 'list')->checkbox()->label(Yii::t('app', 'Show in lists')) ?>
    <?= $form->field($model, 'status')->checkbox()->label(Yii::t('app', 'Active')) ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('croacworks\essentials', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
