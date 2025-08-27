<?php

use croacworks\essentials\controllers\CommonController;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel croacworks\essentials\models\FolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Folders');
$this->params['breadcrumbs'][] = $this->title;

$style = <<< CSS
    .fancybox__content {
        padding: 0 !important;
        margin: 0 !important;
    }
    .fancybox__folder::before, .fancybox__folder::after{
        margin:0!important;
    }
CSS;

$script = <<< JS
    
    let modal = null;

    function clearForms()
    {
        document.getElementById("form-folder").reset();
        $(':input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
    }

    $('#form-folder').on('keyup keypress', function (e) {
        console.log(e);
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13  && e.type == 'keyup') {
            e.preventDefault();
            if($('#folder-name').val().trim() != ''){
                createFolder();
            }
            return false;
        }else if(keyCode !== 13 && e.type == 'keyup' && $('#folder-name').val().trim() != ''){
            console.log('enabled');
            console.log(e.type);
            $('#btn-create-folder').prop('disabled',false);
        }else if(keyCode !== 13 && e.type == 'keyup' && $('#folder-name').val().trim() == ''){
            console.log('disable');
            console.log(e.type);
            $('#btn-create-folder').prop('disabled',true);
        }
        
    });


    function createFolder(){
        $('#overlay_form').show();
        var formData = $("#form-folder").serialize();

        $.ajax({
            type: "POST",
            url: "/folder/add",
            data: formData,
        }).done(function(response) {        
                toastr.success("Folder Created!");
                $.pjax.reload({container: "#list-files-grid", async: true});
                clearForms();
                modal.hide();
        }).fail(function (response) {
            toastr.error("Error on create folder!");
        }).always(function (response) {
            $('#overlay_form').hide();
        });
    }

    $("#btn-create-folder").click(function(){
        createFolder();
    });

    function removeFile(e){

        $(`#overlay-\${e.data('id')}`).show();
    
        $.ajax({
            type: "POST",
            url: e.data('link'),
        }).done(function(response) {     
            if(response == 0){
                toastr.error("Error on delete!");
                return false;
            }   
            toastr.success("Removido!");
            $.pjax.reload({container: "#list-files-grid", async: true});
        }).fail(function (response) {
            toastr.error("Error on delete!");
        }).always(function (response) {
           $(`#overlay-\${e.data('id')}`).hide();
        });

        setTimeout(() => {
            $(`#overlay-\${e.data('id')}`).hide();
        }, 2000);
    }

    $(function(){

        Fancybox.bind("[data-fancybox]");

        modal = new bootstrap.Modal(document.getElementById('create-file'), {
            keyboard: true
        });

        // window.addEventListener('message', function(event) {
        //     if(event.data == 'close'){
        //         Fancybox.close();
        //     } else if(event.data.action == 'save') {
        //         alert(`#\${e.data('id')}-iframe`);
        //         return false;
        //         var iframe = $(`#\${e.data('id')}-iframe`)[0];
        //         iframe.contentWindow.postMessage('show', "*"); 
        //         var formData = event.data.form;
        //             $.ajax({
        //             type: "POST",
        //             url: `\${e.data('link')}` ,
        //             data: formData,
        //         }).done(function(response) {        
        //             if(response == 1){
        //                 Fancybox.close();
        //                 toastr.success("Salvo!");
        //                 $.pjax.reload({container: "#list-folders-grid", async: false});
        //             }else{
        //                 toastr.error("Error ao adicionar file!");
        //             }
        //         }).fail(function (response) {
        //             toastr.error("Error ao adicionar file!");
        //         }).always(function (response) {
        //             iframe.contentWindow.postMessage('hide', "*");
        //         });

        //     }else{
        //         var iframe = $('#' + event.data.id + ' iframe')[0];
        //         iframe.contentWindow.postMessage('show', "*"); 
        //         var formData = event.data.form;
        //             $.ajax({
        //             type: "POST",
        //             url: "/file/edit/" + event.data.id,
        //             data: formData,
        //         }).done(function(response) {        
        //             if(response == 1){
        //                 Fancybox.close();
        //                 toastr.success("Salvo!");
        //                 $.pjax.reload({container: "#list-folders-grid", async: false});
        //             }else{
        //                 toastr.error("Error ao salvar file!");
        //             }
        //         }).fail(function (response) {
        //             toastr.error("Error ao adicionar file!");
        //         }).always(function (response) {
        //             iframe.contentWindow.postMessage('hide', "*");
        //         });
               
        //     }
        // });

    });
  
JS;

$assetsDir =  CommonController::getAssetsDir();

$this::registerJs($script, $this::POS_END);
$this::registerCss($style);

?>
<div class="container-fluid">


    <!-- Modal -->
    <div class="modal fade" id="create-file" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel"><?= Yii::t('app', 'New Folder'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="overlay_form" class="overlay" style="height: 100%;position: absolute;width: 100%;z-index: 3000;display:none;top:0;left:0;">
                    <div class="fa-3x">
                        <i class="fas fa-sync fa-spin"></i>
                    </div>
                </div>
                <div class="modal-body" style="font-size:1em;">
                    <?php $form = ActiveForm::begin(['id' => 'form-folder']); ?>

                    <div class="form-group col-md-12">
                        <?= $form->field($model_folder, 'name')->textInput(['maxlength' => true]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="javascript:modal.hide();"> <?= Yii::t('app', 'Cancel'); ?></button>
                    <button disabled id="btn-create-folder" type="button" class="btn btn-success" ><?= Yii::t('app', 'Create Folder'); ?></button>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">

                        <div class="col-md-12">

                            <button class="btn btn-success" onclick="javascript:modal.show();">
                                <i class="fas fa-plus-square"></i> <?= Yii::t('app', 'New Folder'); ?>
                            </button>

                        </div>

                    </div>

                    <?php // echo $this->render('_search', ['model' => $searchModel]); 
                    ?>

                    <div id="main-content" class="file_manager">
                        <div class="container">

                            <?php Pjax::begin(['id' => 'list-files-grid', 'options' => ['class' => 'row clearfix']]) ?>
                            <?php
                            echo yii\widgets\ListView::widget([
                                'dataProvider' => $dataProvider, 'itemView' => '_parts/_file',
                                'layout' => "{items}{pager}", 'options' => ['tag' => false,],
                                'itemOptions' => ['tag' => false,],
                                'pager' => [
                                    'options' => ['tag' => 'ul', 'class' => 'pagination justify-content-center mb-4',],
                                ],
                            ]);
                            ?>
                            <?php Pjax::end() ?>

                        </div>
                    </div>

                </div>
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>