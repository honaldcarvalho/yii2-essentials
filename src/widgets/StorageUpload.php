<?php

namespace croacworks\essentials\widgets;

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\themes\coreui\assets\PluginAsset;
use Yii;
use yii\web\View;
use yii\bootstrap5\Widget;

/** @var yii\web\View $this */
/** @var croacworks\essentials\models\File $model */
/** @var yii\widgets\ActiveForm $form */

/**
 * USAGE: 
    <?= croacworks\essentials\widgets\StorageUpload::widget([
        'folder_id' => $model->id, //Folder model id
        'grid_reload'=>1, //Enable auto reload GridView. disable = 0; enable = 1;
        'grid_reload_id'=>'#list-files-grid', //ID of GridView will reload
        'crop_aspect_ratio'=>'1/1', //Aspect ratio for crop image
        'crop_force' => 0 //Force crop image
    ]); ?>

 */
class StorageUpload extends Widget
{
    public $token;
    public $random;

    /** Aspect ratio for crop image */
    public $crop_aspect_ratio = 'null';
    /** Force crop image */
    public $crop_force = 0;
    /** Folder model id */
    public $folder_id = 1;
    /** Enable auto reload GridView. disable = 0; enable = 1; */
    public $grid_reload = 0;
    /** ID of GridView will reload */
    public $grid_reload_id = '#list-files-grid';

    public function init(): void
    {
        parent::init();
        $this->token =  AuthorizationController::User()->access_token;
        $this->random =  CommonController::generateRandomString(6);
        PluginAsset::register(Yii::$app->view)->add(['axios','jquery-cropper']);
    }

    public function run()
    {
        $css = <<< CSS

            #progress-bar-{$this->random} {
                height: 100%;
                width: 0%;
                transition: width 0.4s;
                border-radius: 4px;
            }
            
            .card-info {
                display: flex;
                flex-direction: row;
                width: 100%;
                height: 250px;
                max-width: 100%;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .card-content {
                flex: 1;
                padding: 20px;
            }

            .card-content ul {
                list-style-type: none;
                padding: 0;
            }

            .card-content ul li {
                margin-bottom: 10px;
                font-size: 16px;
            }

            .light-mode .card-content ul li {
                color:#333;
            }

            .dark-mode .card-content ul li {
                color:#fff;
            }

            .card-image {
                width: 50%;
                max-width: 250px;
                overflow: hidden;
            }

            .card-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            @media (max-width: 768px) {
                .card-info {
                    flex-direction: column;
                    max-width: 100%;
                }

                .card-image {
                    width: 100%;
                    max-height: 300px;
                }
            }
        CSS;

        \Yii::$app->view->registerCss($css);
        Yii::$app->view->registerJs(<<<JS
        onPjaxReady((root) => {

            function el(id){
                return document.getElementById(id);
            }

            const modal_crop = new bootstrap.Modal(el('modal-crop-{$this->random}'), {
                keyboard: false,
                backdrop: "static"
            });
            let temp_image;
            let file_input = el("file-input-{$this->random}");
            let preview = el("preview-{$this->random}");
            let img_crop = el("img-crop-{$this->random}");
            let card_info = el('card-info-{$this->random}');
            let upload_button = el('upload-button-{$this->random}');
            let crop_button = el('crop-button-{$this->random}');
            let progressBar = el('progress-bar-{$this->random}');
            let progress_container = el('progress-{$this->random}');
            let input_container = el('input-{$this->random}');
            let editing = false;

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024; // Define the constant for kilobyte
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB']; // Units
                const i = Math.floor(Math.log(bytes) / Math.log(k)); // Determine the unit index
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function isImage(file){
                const fileType = file.type;
                const validImageTypes = ["image/jpeg", "image/png", "image/gif", "image/bmp", "image/webp"];
                if (validImageTypes.includes(fileType)) {
                    return true;
                }
                return false;
            }

            async function encodeImageFileAsURL(file,preview) {
                return new Promise((resolve, reject) => {
                    var reader = new FileReader();            
                    reader.onloadend = function () {
                        preview.src = reader.result;
                        resolve(); // Resolving the promise when the encoding is complete
                    };
                    reader.onerror = reject; // Rejecting the promise in case of an error
                    reader.readAsDataURL(file);
                });
            }

            file_input.addEventListener('change', async function(event) {

                var files = event.target.files;
                if (files.length > 0) {
                    if(isImage(files[0])){
                        if($this->crop_force == 1){
                            await encodeImageFileAsURL(files[0],img_crop);
                            modal_crop.show();
                            startCrop();
                            editing = true;
                            crop_button.disabled = false;
                        } else{
                            await encodeImageFileAsURL(files[0],preview);
                            await encodeImageFileAsURL(files[0],img_crop);
                            crop_button.disabled = false;
                        }
                    }else{
                        preview.src = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW';
                        crop_button.disabled = true;
                    }
                    el("preview-name-{$this->random}").innerHTML = files[0].name;
                    el("preview-type-{$this->random}").innerHTML =files[0].type;
                    el("preview-size-{$this->random}").innerHTML =formatFileSize(files[0].size);
                    card_info.classList.remove('d-none');
                    upload_button.disabled = false;
                }
                
            });

            /*CROP FUNTIONS*/

            function startCrop() {
                temp_image = $("#img-crop-{$this->random}");
                temp_image.cropper({
                    initialAspectRatio: $this->crop_aspect_ratio,
                    aspectRatio: $this->crop_aspect_ratio,
                    viewMode: 3,
                    crop: function (event) { }
                });
                cropperImage = temp_image.data('cropper');
            }
            
            function cancelCrop() {
                temp_image.cropper("destroy");

                if($this->crop_force == 1){
                    file_input.value = '';
                    upload_button.disabled = true;
                    crop_button.disabled = true;
                    input_container.classList.remove('d-none');
                    progress_container.classList.add('d-none');
                    card_info.classList.add('d-none');
                }
            }
            
            function setCrop() {
                
                var canvas = cropperImage.getCroppedCanvas();
                var data = canvas.toDataURL();
                preview.src =  data;
                img_crop.src = data;
                
                temp_image.cropper("destroy");
            
                canvas.toBlob(function (blob) {
                    var file = new File([blob], "{$this->random}-imagem.jpg", {type: "image/jpeg", lastModified: new Date().getTime()});
                    var container = new DataTransfer();
                    container.items.add(file);
                    file_input.files = container.files;
                });

            }

            el('crop-button-{$this->random}').addEventListener('click', (e) => {
                if (editing === false) {
                    modal_crop.show();
                    startCrop();
                    editing = true;
                }
            });

            el('btn-cancel-crop-{$this->random}').addEventListener('click',function () {
                if (editing) {
                    modal_crop.hide();
                    cancelCrop();
                    editing = false;
                }
            });
        
            el('btn-set-crop-{$this->random}').addEventListener('click',function () {
                el('crop-load-{$this->random}').classList.remove('d-none');
                if (editing) {
                    /** para garantir que o overlay apareça */
                    setTimeout(function() { 
                        modal_crop.hide();
                        setCrop();
                        editing = false;
                        el('crop-load-{$this->random}').classList.add('d-none');
                    }, 100); 
                }
            });

            /*END CROP FUNTIONS*/

            el('upload-button-{$this->random}').addEventListener('click', (e) => {

                var file = file_input.files[0];

                if (!file) {
                    alert('Please select a file first.');
                    return;
                }

                // Create a FormData object
                const formData = new FormData();
                formData.append('file', file);
                formData.append('folder_id', $this->folder_id);
                formData.append('save', 1);

                input_container.classList.add('d-none');
                progress_container.classList.remove('d-none');
                upload_button.disabled = true;

                axios.post('/rest/storage/send', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Authorization': `Bearer {$this->token}`
                    },
                    onUploadProgress: (progressEvent) => {
                        // Calculate the progress percentage
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        // Update the progress bar width
                        progressBar.style.width = `\${percentCompleted}%`;
                    }
                })
                .then(response => {
                    if(response.data.success){
                        toastr.success("File sended! " + response.data['message']);     
                        file_input.value = '';
                        upload_button.disabled = true;
                        crop_button.disabled = true;
                    }else{
                        toastr.error("Error on send file! " + response.data['message']); 
                    }
                    progressBar.style.width = '0%';

                    if({$this->grid_reload} == 1){
                        $.pjax.reload({container: "{$this->grid_reload_id}", async: true,timeout : false});
                    }
                })
                .catch(error => {
                    toastr.error("Error on send file! " + error);
                }).then(response => {
                    input_container.classList.remove('d-none');
                    progress_container.classList.add('d-none');
                    card_info.classList.add('d-none');
                });

            });
        });
        JS);

        $cut_label = Yii::t('app', 'Cut');
        $cancel_label = Yii::t('app', 'Cancel');
        $upload_label = Yii::t('app', 'Upload');
        $edit_label = Yii::t('app', 'Edit');

        $modal = <<< HTML
            <div class="modal position-relative" tabindex="-1" id="modal-crop-{$this->random}" >

                <div id="crop-load-{$this->random}" class="justify-content-center d-none" style="width:100%;height:100%;position:absolute;background:#000000ab;top:0;left:0;z-index:3000;">
                    <div class="d-flex justify-content-center">
                        <h4>Cropping... Wait...</h4>
                        <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cropping...</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body p-0" >
                            <img id="img-crop-{$this->random}" class="img-fluid rounded mb-2 mb-lg-0 " src="/dummy/code.php?x=300x300/fff/000.jpg&text=PREVIEW" style="width:100%;max-width:60%;max-width:60%;">
                        </div>
                        <div class="modal-footer" id="crop-footer">
                            <a class="btn btn-warning" href="javascript:;" id="btn-set-crop-{$this->random}"><i class="fas fa-cut"></i> {$cut_label} </a>
                            <a class="btn btn-danger" href="javascript:;" id="btn-cancel-crop-{$this->random}"><i class="fas fa-window-close"></i> {$cancel_label} </span></a>
                        </div>
                    </div>
                </div>
            </div>
        HTML;

        $form_upload = <<< HTML
            $modal
            <div class="card-info d-none" id="card-info-{$this->random}">
                <div class="card-image">
                    <img src="https://via.placeholder.com/300" id="preview-{$this->random}" alt="Placeholder Image">
                </div>
                <div class="card-content">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" id="preview-name-{$this->random}"></li>
                        <li class="list-group-item" id="preview-type-{$this->random}"></li>
                        <li class="list-group-item" id="preview-size-{$this->random}"></li>
                    </ul>
                </div>
            </div>
            <div class="btn-group mt-2" role="group" id="input-{$this->random}">
                <button class="btn btn-info position-relative">
                    <input type="file" class="position-absolute z-0 opacity-0 w-100 h-100"  id="file-input-{$this->random}" aria-label="Upload">
                    <i class="fas fa-folder-open m-2"></i> Select File
                </button>
                <a class="btn btn-warning" id="upload-button-{$this->random}" disabled="true"> <i class="fas fa-upload m-2"></i> Upload</a>
                <a class="btn btn-default" id="crop-button-{$this->random}" disabled="true"> <i class="fas fa-cut m-2"></i> Crop</a>
            </div>

            <div class="progress d-none" id="progress-{$this->random}">
                <div id="progress-bar-{$this->random}" class="progress-bar progress-bar-striped bg-success progress-bar-animated"></div>
            </div>

        HTML;
        echo $form_upload;
    }
}
