<?php

    $path = explode('\\',get_class($searchModel));
    $modelName = end($path);
    $collapsed = 'collapsed-card';
    $display = 'none';

    if(isset($_GET["{$modelName}"])){

        foreach ($_GET["{$modelName}"] as $parametro){
            if(!empty($parametro)){
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
    <div class="col-md-12">

        <div class="card <?= $collapsed ?>">
            <div class="btn card-header" data-card-widget="collapse" title="Collapse">
                <label class="card-title text-weebz"><i class="fa fa-filter"></i> <?= Yii::t('app','Filtros')?></label>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="display:<?= $display ?>;">
                <div class="col-md-12">

                    <?= $this->render("{$view}/_search", [
                        'model' => $searchModel
                    ]) ?>

                </div>
            </div>
        </div>
    </div>

</div>