<?php

$path = explode('\\', get_class($searchModel));
$modelName = end($path);
$collapsed = 'collapsed-card';
$display = 'none';

if (isset($_GET["{$modelName}"])) {
    foreach ($_GET["{$modelName}"] as $parametro) {
        if (!empty($parametro)) {
            $collapsed = "";
            $display = 'block';
        }
    }
}

$script = <<< JS
function clearFilters(){
    document.querySelectorAll('form').forEach(form => form.reset());
    document.querySelectorAll('select').forEach(select => {
        select.value = null;
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(select).val(null).trigger('change');
        }
    });
    document.querySelectorAll(':checkbox, :radio').forEach(el => el.checked = false);
    return false;
}

document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.btn-reset').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            clearFilters();
        });
    });

    const collapseEl = document.getElementById('collapseSearch');
    const icon = document.querySelector('#collapseToggleIcon');
    if (collapseEl && icon) {
        collapseEl.addEventListener('show.bs.collapse', () => {
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
        });
        collapseEl.addEventListener('hide.bs.collapse', () => {
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        });
    }
});
JS;

$this::registerJs($script, \yii\web\View::POS_END);

?>
<div class="card">
    <div class="card-header">
        <button type="button" class="btn btn-tool w-100" data-coreui-toggle="collapse" href="#collapseSearch" role="button" aria-expanded="false" aria-controls="collapseSearch">
            <span class="float-start"><i class="fa fa-filter"></i> <?= Yii::t('app', 'Filters') ?></span>
            <i id="collapseToggleIcon" class="fas fa-plus float-end"></i>
        </button>
    </div>

    <div class="collapse" id="collapseSearch">
        <div class="card card-body <?= $collapsed ?>">
            <?= $this->render("{$view}/_search", [
                'model' => $searchModel
            ]) ?>
        </div>
    </div>
</div>
