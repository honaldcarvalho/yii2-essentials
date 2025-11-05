<?php

use croacworks\essentials\widgets\DynamicFormWidget;
use yii\widgets\Pjax;

?>

<div class="container-fluid">
    <div class="card">
        
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?=$model->name;?></h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <?php
                    Pjax::begin(['id' => 'pjax-dynamic-form']);
                    echo DynamicFormWidget::widget(['formId' => $model->id]);
                    Pjax::end();
                    ?>
                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>