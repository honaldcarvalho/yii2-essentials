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

            $('form').trigger("reset");
            $('select').val(null).trigger('change');
            $(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
            $(':checkbox, :radio').prop('checked', false);
            return false;
        }

        $(function(){
            $('.btn-reset').on('click',function(e){
                e.preventDefault();
                clearFilters();
            });
        });
    JS;

$this::registerJs($script);

?>
<div class="row">



    <p class="d-inline-flex gap-1">
        <label class="card-title text-weebz"><i class="fa fa-filter"></i> <?= Yii::t('app', 'Filtros') ?></label>
    <div class="card-tools">
        <button type="button" class="btn btn-tool" data-coreui-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
            <i class="fas fa-plus"></i>
        </button>
    </div>
    </p>
    <div class="collapse" id="collapseExample">
        <div class="card card-body <?= $collapsed ?>">
            <?= $this->render("{$view}/_search", [
                'model' => $searchModel
            ]) ?>

        </div>
    </div>

</div>