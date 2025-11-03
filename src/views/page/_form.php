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

$initTags = [];
$suggestUrl = Url::to(['/tag/suggest']);
$searchUrl  = Url::to(['/tag/search']); // já existente

if (!$model->isNewRecord && !empty($model->tagIds)) {
    $initTags = Tag::find()
        ->select(['name', 'id'])
        ->where(['id' => (array)$model->tagIds])
        ->indexBy('id')
        ->column(); // [id => name]
}

$inputId = Html::getInputId($model, 'tagIds');

$this->registerJs(<<<JS
(function(){

  var el = $('#page-tagids');

  el.select2({
    width: '100%',
    placeholder: yii.t('app', 'Select or type tags...'),
    tags: true,
    tokenSeparators: [','],
    minimumInputLength: 1,
    ajax: {
      url: '/blog/tag/search',
      dataType: 'json',
      delay: 250,
      data: function(params){ return { q: params.term }; },
      processResults: function(data){ return { results: data }; }
    },
    createTag: function(params){
      var term = (params.term || '').trim();
      if (term === '') return null;
      return { id: term, text: term, newTag: true };
    },
    templateSelection: function(item){ return item.text || item.id; },
    escapeMarkup: function(m){ return m; }
  });
})();

// Pega o valor de um campo pelo ID
function getTextValue(id) {
  // Se for um campo TinyMCE ativo
  if (typeof tinyMCE !== 'undefined' && tinyMCE.get(id)) {
    return tinyMCE.get(id).getContent({ format: 'text' }).trim();
  }

  // Caso contrário, pega direto do input/textarea normal
  var node = document.getElementById(id);
  return node ? (node.value || '').trim() : '';
}

// Adiciona uma tag no <select multiple>
function addTagToSelect(selectId, text, value) {
  var select = document.getElementById(selectId);
  if (!select) return;
  var val = value || text;
  var exists = Array.from(select.options).some(function(o) {
    return String(o.value) === String(val);
  });
  if (!exists) {
    var opt = new Option(text, val, true, true);
    select.add(opt);
    $(select).trigger('change'); // dispara evento pro select2
  }
}

function fetchTagSuggestions(url, selectId, fields) {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.triggerSave) {
    tinyMCE.triggerSave();
  }

  var payload = {};
  fields.forEach(function(f) {
    payload[f.name] = getTextValue(f.id);
  });

  if (!payload.title && !payload.description && !payload.content) {
    alert('Preencha título, descrição ou conteúdo.');
    return;
  }

  // Adiciona seed aleatório para forçar novas sugestões
  payload.seed = Math.floor(Math.random() * 999999);
  payload.language_id = document.getElementById('page-language_id')?.value || '';

  fetch(url, {
    method: 'page',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(payload)
  })
  .then(r => r.json())
  .then(json => {
    var list = (json && json.suggestions) ? json.suggestions : [];
    var wrap = document.getElementById('tag-suggestions');

    // Cria estrutura principal se ainda não existir
    if (!wrap.querySelector('.suggestion-container')) {
      wrap.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Sugestões de tags</strong>
          <button id="btn-clear-suggestions" type="button" class="btn btn-sm btn-outline-danger">Limpar</button>
        </div>
        <div class="suggestion-container"></div>
      `;

      // Evento do botão de limpar
      wrap.querySelector('#btn-clear-suggestions').addEventListener('click', function() {
        wrap.innerHTML = '';
      });
    }

    var container = wrap.querySelector('.suggestion-container');

    if (!list.length) {
      if (!container.innerHTML.trim()) {
        container.innerHTML = '<small class="text-muted">Nenhuma sugestão encontrada</small>';
      }
      return;
    }

    // Adiciona novas sugestões (sem duplicar)
    list.forEach(sug => {
      var existsBtn = Array.from(container.querySelectorAll('button')).some(btn =>
        btn.textContent.trim().toLowerCase() === sug.text.toLowerCase()
      );
      if (existsBtn) return;

      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm me-1 mb-1 ' + (sug.exists ? 'btn-outline-primary' : 'btn-outline-success');
      b.textContent = sug.text;

      // Ao clicar: adiciona ao select e remove o botão
      b.addEventListener('click', function() {
        addTagToSelect(selectId, sug.text, sug.id || null);
        b.remove();

        // Remove bloco se não houver mais sugestões
        if (!container.querySelector('button')) {
          wrap.innerHTML = '';
        }
      });

      container.appendChild(b);
    });
  })
  .catch(err => {
    console.error(err);
    alert('Erro ao gerar sugestões.');
  });
}

document.getElementById('btn-auto-summary').addEventListener('click', async function() {
  if (typeof tinyMCE !== 'undefined' && tinyMCE.triggerSave) tinyMCE.triggerSave();

  const title = getTextValue('page-title');
  const description = getTextValue('page-description');
  const content = getTextValue('page-content');
  const langId = document.getElementById('page-language_id')?.value || '';

  if (!title && !description && !content) {
    Swal.fire({
      icon: 'info',
      title: yii.t('app', 'Nada para resumir'),
      text: yii.t('app', 'Preencha título, descrição ou conteúdo antes.'),
    });
    return;
  }

  Swal.fire({
    title: yii.t('app', 'Gerando resumo...'),
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  const res = await fetch('/blog/tag/summarize', {
    method: 'page',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ title, description, content, language_id: langId })
  });
  const json = await res.json();

  Swal.close();

  if (json.success) {
    setFieldText('page-description', json.summary);
    Swal.fire({
      icon: 'success',
      title: yii.t('app', 'Resumo gerado!'),
      text: yii.t('app', 'A descrição foi preenchida automaticamente.'),
      toast: true,
      position: 'bottom-end',
      timer: 4000,
      showConfirmButton: false
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: yii.t('app', 'Erro ao gerar resumo'),
      text: json.message || 'Ocorreu um erro ao gerar a descrição.',
    });
  }
});

// Evento do botão principal
document.getElementById('btn-suggest-tags').addEventListener('click', function() {
  fetchTagSuggestions(
    '/blog/tag/suggest',
    'page-tagids',
    [
      { id: 'page-title', name: 'title' },
      { id: 'page-description', name: 'description' },
      { id: 'page-content', name: 'content' }
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
        'mode'        => 'defer',
        'model'       => $model,
        'attribute'   => 'file_id',
        'fileInputId' => \yii\helpers\Html::getInputId($model, 'file_id'),
        'imageUrl'    => $model->file->url ?? '',
        'aspectRatio' => '4/3',
    ]) ?>

    <?= $form->field($model, 'page_section_id')->dropDownList(
        yii\helpers\ArrayHelper::map(PageSection::find()->all(), 'id', 'name'),
        ['prompt' => '-- selecione uma secção --']
    ) ?>

    <div class="col-sm-3">
        <?= $form->field($model, 'language_id')->dropDownList(
            yii\helpers\ArrayHelper::map(Language::find()->all(), 'id', 'name'),
            [
                'prompt' => Yii::t('app', 'Select Language'),
                'options' => \yii\helpers\ArrayHelper::map(
                    Language::find()->all(),
                    'id',
                    fn($lang) => ['data-code' => $lang->code]
                ),
            ]
        ) ?>
    </div>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <div class="row mb-3">
        <div class="col-sm-12">
            <?= $form->field($model, 'description')->textarea(['rows' => 2]) ?>
            <button type="button" id="btn-auto-summary" class="btn btn-outline-secondary btn-sm">
                <?= Yii::t('app', 'Gerar descrição automática') ?>
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
                $initTags, // opções já selecionadas com os textos
                [
                    'id'       => $inputId,
                    'multiple' => true,
                    'class'    => 'form-control select2-plain', // classe só pra selecionar no JS/CSS
                ]
            )->label(Yii::t('app', 'Tags')) ?>

            <button type="button" id="btn-suggest-tags" class="btn btn-outline-secondary btn-sm">
                Sugerir tags
            </button>
            <div id="tag-suggestions" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    </div>

    <?= $form->field($model, 'list')->checkbox() ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group mb-3 mt-3">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>' . Yii::t('croacworks\essentials', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>