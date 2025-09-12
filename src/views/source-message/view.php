<?php

use croacworks\essentials\models\Language;
use croacworks\essentials\models\SourceMessage;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\SourceMessage */

$this->title = Yii::t('app', 'Source Message #{name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Source Messages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$script = <<< JS

    let modal = new bootstrap.Modal(document.getElementById('add-translate'), {
        keyboard: true
    });

    $(function(){
        $("#form-translate").on("keyup", function(e) {

            $('#btn-add-translate .icon').removeClass('fa-plus-circle mr-2 ');
            $('#btn-add-translate .icon').addClass('fa-sync fa-spin');
            $('#btn-add-translate').prop('disabled',true);

            setTimeout(() => {
                console.log('time');
                validateItem();    
            }, 1000);

        });
    });

    function validateItem()
    {
        $('#btn-add-translate .icon').addClass('fa-plus-circle mr-2 ');
        $('#btn-add-translate .icon').removeClass('fa-sync fa-spin');
        if(
            $('#message-language').find(':selected').val() !== undefined && $('#message-language').find(':selected').val() !== '' &&
            $('#message-translation').val() !== undefined && $('#message-translation').val() !== ''
        ){
            $('#btn-add-translate').prop('disabled',false);
            return true;
        }else{
            $('#btn-add-translate').prop('disabled',true);
            return false;
        }
    }

    function clearForms()
    {
        document.getElementById("form-translate").reset();
        $(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
        $('#btn-add-translate').prop('disabled',false);
        $('select').val(null).trigger('change');
        return true;
    }
    
    function removeTranslate(e) {

        let el = $(e);

        if (confirm('You really can remove this translate?')) {

            object = el.children("i");
            let old_class = el.children("i").attr('class');
            object.removeClass(old_class);
            object.addClass('fas fa-sync fa-spin');

            $.ajax({
                type: "POST",
                url: el.data('link'),
            }).done(function(response) {     
                if(response == 0){
                    toastr.error("Error on update status!");
                    return false;
                }
                toastr.success("Translate Removed!");
                $.pjax.reload({container: "#list-translates-grid", async: true});
            }).fail(function (response) {
                toastr.error("Error on update status!");
            }).always(function (response) {
                object.removeClass('fas fa-sync fa-spin');
                object.attr('class',old_class);
            });

        }

        return false;

    }

    function createTranslate(){
        $('#overlay_form').show();
        var formData = $("#form-translate").serialize();

        $.ajax({
            type: "POST",
            url: "/source-message/add-translation",
            data: formData,
        }).done(function(response) {        
                toastr.success("Translate Added!");
                
                $.pjax.reload({container: "#list-translates-grid", async: true});
                modal.hide();
                clearForms();
        }).fail(function (response) {
            toastr.error("Error on create translate!");
        }).always(function (response) {
            $('#overlay_form').hide();
        });
    }

    $("#btn-add-translate").click(function(){
        createTranslate();
    });
JS;

$this::registerJs($script, $this::POS_END);

?>

<!-- Modal -->
<div class="modal fade" id="add-translate" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel"><?= Yii::t('app', 'New Translate'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="overlay_form" class="overlay" style="height: 100%;position: absolute;width: 100%;z-index: 3000;display:none;top:0;left:0;">
                <div class="fa-3x">
                    <i class="fas fa-sync fa-spin"></i>
                </div>
            </div>
            <div class="modal-body" style="font-size:1em;">
                <?php $form = ActiveForm::begin(['id' => 'form-translate']); ?>

                <?= $form->field($model_translate, 'id')->hiddenInput(['value' => $model->id])->label(false); ?>

                <div class="form-group col-md-12">
                    <?= $form->field($model_translate, 'language')->dropdownList(yii\helpers\ArrayHelper::map(Language::find()->asArray()->all(), 'code', 'name'), ['class' => 'form-control']) ?>
                </div>
                <div class="form-group col-md-12">
                    <?= $form->field($model_translate, 'translation')->textInput(['maxlength' => true]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="javascript:modal.hide();"> <?= Yii::t('app', 'Cancel'); ?></button>
                <button disabled id="btn-add-translate" type="button" class="btn btn-success"><i class="fas fa-plus-circle mr-2 icon"></i> <?= Yii::t('app', 'Add Translate'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget(['controller' => Yii::$app->controller->id, 'model' => $model, 'verGroup' => false]) ?>
                        <button class="btn btn-success" onclick="javascript:modal.show();">
                            <i class="fas fa-plus-square"></i> <?= Yii::t('app', 'New Translate'); ?>
                        </button>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'category',
                            'message:ntext',
                        ],
                    ]) ?>
                </div>

            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>



<div class="card">
    <div class="card-header">
        <h4><?= Yii::t('app', 'Translates') ?></h4>
    </div>
    <div class="card-body">
        <div class="row">

            <?php Pjax::begin(['id' => 'list-translates-grid', 'options' => ['class' => 'col-md-12']]) ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'header' => Yii::t('app', 'Source Message'),
                            'header' => 'Mensage',
                            'value' => function ($data) {
                                return $data->sourceMessage->message;
                            },
                        ],
                        'language',
                        'translation:ntext',

                        ['class' => 'croacworks\essentials\components\gridview\ActionColumn', 'verGroup' => false, 'controller'=>'message',
                            'template' => '{view}{remove}',
                            'buttons' => [  
                                'remove' => function ($url, $model, $key) {
                                    return Html::a('<i class="fas fa-trash"></i>','javascript:;',
                                     [
                                        'onclick'=>'removeTranslate(this);', 
                                        'data-link'=> "/message/del/{$model->id}?language={$model->language}",
                                        'class'=>'btn btn-outline-secondary remove',"data-toggle"=>"tooltip","data-placement"=>"top", 
                                        "title"=>\Yii::t('app','Remove from folder')
                                    ]);
                                },    
                            ]
                        ],
                    ],
                    'summaryOptions' => ['class' => 'summary mb-2'],
                    'pager' => [
                        'class' => 'yii\bootstrap5\LinkPager',
                    ]
                ]); ?>
            <?php Pjax::end() ?>

        </div>
    </div>
    <!--.card-body-->
</div>
<!--.card-->
</div>
<!--.col-md-12-->