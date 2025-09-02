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
<div class="card">
    <div class="card-header">
        <div class="d-flex ms-auto">
            <button type="button" class="btn btn-tool" data-coreui-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                <i class="fa fa-filter"></i> <?= Yii::t('app', 'Filters') ?> <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="btn-clipboard mt-0 me-0" aria-label="Copy to clipboard" data-coreui-original-title="Copy to clipboard">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">
                    <polygon fill="var(--ci-primary-color, currentColor)" points="408 432 376 432 376 464 112 464 112 136 144 136 144 104 80 104 80 496 408 496 408 432" class="ci-primary"></polygon>
                    <path fill="var(--ci-primary-color, currentColor)" d="M176,16V400H496V153.373L358.627,16ZM464,368H208V48H312V200H464Zm0-200H344V48h1.372L464,166.627Z" class="ci-primary"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="collapse" id="collapseExample">
        <div class="card card-body <?= $collapsed ?>">
            <?= $this->render("{$view}/_search", [
                'model' => $searchModel
            ]) ?>

        </div>
    </div>

</div>