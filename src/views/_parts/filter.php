<?php
$path = explode('\\', get_class($searchModel));
$modelName = end($path);

// detecta se deve iniciar aberto
$isOpen = false;
if (isset($_GET[$modelName])) {
    foreach ((array)$_GET[$modelName] as $valor) {
        if ($valor !== '' && $valor !== null) {
            $isOpen = true;
            break;
        }
    }
}

$js = <<<JS
(function initFilterCollapse(){
  function setIcon(open){
    var icon = document.getElementById('collapseToggleIcon');
    if (!icon) return;
    icon.classList.toggle('fa-plus', !open);
    icon.classList.toggle('fa-minus', open);
  }

  var collapseEl = document.getElementById('collapseExample');
  if (!collapseEl) return;

  // estado inicial (caso já venha "show")
  setIcon(collapseEl.classList.contains('show'));

  // ouvintes CoreUI 5
  collapseEl.addEventListener('show.coreui.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('shown.coreui.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('hide.coreui.collapse', function(){ setIcon(false); });
  collapseEl.addEventListener('hidden.coreui.collapse', function(){ setIcon(false); });

  // fallback Bootstrap (se aplicável)
  collapseEl.addEventListener('show.bs.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('shown.bs.collapse', function(){ setIcon(true); });
  collapseEl.addEventListener('hide.bs.collapse', function(){ setIcon(false); });
  collapseEl.addEventListener('hidden.bs.collapse', function(){ setIcon(false); });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);

$js = <<<JS
(function attachUniversalReset(){
  function clearElementValue(el){
    if (el.disabled) return;

    var tag = el.tagName.toLowerCase();
    var type = (el.getAttribute('type') || '').toLowerCase();

    // Não mexer em inputs hidden (ex.: _csrf, cenários, etc.)
    if (tag === 'input' && type === 'hidden') return;

    // Text-like
    var textLikes = ['text','password','email','number','url','tel','search','color','date','datetime-local','month','time','week','range'];
    if (tag === 'input' && textLikes.indexOf(type) !== -1){
      el.value = '';
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }

    // Checkbox / Radio
    if (tag === 'input' && (type === 'checkbox' || type === 'radio')){
      el.checked = false;
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }

    // Arquivo
    if (tag === 'input' && type === 'file'){
      // Reseta com troca de tipo (workaround cross-browser)
      try {
        el.value = '';
        if (el.value) {
          el.type = 'text'; el.type = 'file';
        }
      } catch(e){}
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }

    // Textarea
    if (tag === 'textarea'){
      el.value = '';
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }

    // Select (simples ou múltiplo)
    if (tag === 'select'){
      // se for select2, tratamos abaixo também
      // limpa seleção nativa
      if (el.multiple) {
        Array.from(el.options).forEach(function(opt){ opt.selected = false; });
      } else {
        el.selectedIndex = -1; // nada selecionado
      }
      el.value = null;
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }
  }

  function resetYiiActiveFormIfAny(form){
    // Se Yii ActiveForm estiver presente, reseta estados/erros
    if (window.jQuery && jQuery.fn && typeof jQuery(form).yiiActiveForm === 'function'){
      try { jQuery(form).yiiActiveForm('resetForm'); } catch(e){}
    } else {
      // fallback: remove classes de erro comuns
      form.querySelectorAll('.is-invalid, .has-error').forEach(function(el){
        el.classList.remove('is-invalid','has-error');
      });
      form.querySelectorAll('.invalid-feedback, .help-block').forEach(function(el){
        // esvazia mensagens visuais
        if (!el.classList.contains('help-block-help')) el.textContent = '';
        el.style.display = 'none';
      });
    }
  }

  function resetSelect2IfAny(form){
    // Reseta Select2 (v4/v4.1) se jQuery+select2 disponíveis
    if (window.jQuery && jQuery.fn && jQuery.fn.select2){
      jQuery(form).find('select').each(function(){
        var hasSelect2 = jQuery(this).data('select2') || this.classList.contains('select2-hidden-accessible');
        if (hasSelect2){
          jQuery(this).val(null).trigger('change.select2');
        }
      });
    }
  }

  function resetFormHard(form){
    // Limpa cada campo manualmente (não depende de "form.reset()" para evitar voltar valores default)
    var elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(clearElementValue);

    // Após limpar nativamente, trate Select2 para manter UI sincronizada
    resetSelect2IfAny(form);

    // Limpa erros/estados do Yii ActiveForm
    resetYiiActiveFormIfAny(form);
  }

  // Delegação para todos os botões .btn-reset
  document.addEventListener('click', function(ev){
    var target = ev.target.closest('.btn-reset');
    if (!target) return;

    ev.preventDefault();

    // Prioridade 1: data-form="#idDoForm"
    var formSelector = target.getAttribute('data-form');
    var form = null;

    if (formSelector) {
      form = document.querySelector(formSelector);
    }

    // Prioridade 2: form mais próximo do botão
    if (!form) {
      form = target.closest('form');
    }

    // Prioridade 3: primeiro form dentro do card collapse
    if (!form) {
      var card = target.closest('.card');
      if (card) form = card.querySelector('form');
    }

    if (!form) return;

    resetFormHard(form);
  });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_END);

?>

<div class="card mb-3">
  <div class="card-header">
    <button
      type="button"
      class="btn btn-tool w-100"
      data-coreui-toggle="collapse"
      data-coreui-target="#collapseExample"
      aria-controls="collapseExample"
      aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
    >
      <span class="float-start">
        <i class="fa fa-filter"></i> <?= Yii::t('app', 'Filters') ?>
      </span>
      <i id="collapseToggleIcon"
         class="fas <?= $isOpen ? 'fa-minus' : 'fa-plus' ?> float-end"></i>
    </button>
  </div>

  <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="collapseExample">
    <div class="card card-body">
      <?= $this->render("{$view}/_search", ['model' => $searchModel]) ?>
    </div>
  </div>
</div>
