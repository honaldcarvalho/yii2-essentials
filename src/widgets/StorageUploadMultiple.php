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
 * <?= StorageUpload::widget([
 *      'folder_id' => $model->id, //Folder model id
 *      'grid_reload'=>1, //Enable auto reload GridView. disable = 0; enable = 1;
 *      'grid_reload_id'=>'#list-files-grid', //ID of GridView will reload
 *     ]); ?>
 * 
 * Attact file to model
    <?= StorageUploadMultiple::widget([
    'group_id' => AuthorizationController::userGroup(),
    'attact_model'=>[
        'class_name'=> 'weebz\\yii2basics\\models\\PageFile',
        'id'=> $model->id,
        'fields'=> ['page_id','file_id']
    ],
    'grid_reload'=>1,
    'grid_reload_id'=>'#list-files-grid'
    ]); ?>
 */
class StorageUploadMultiple extends Widget
{
    public $token;
    public $random;

    /** Folder model id */
    public $thumb_aspect = 1;
    /** Folder model id */
    public $folder_id = 1;
    /** Folder group id */
    public $group_id = 1;
    /** Model name to attact files */
    public $attact_model = 0;
    /** Model id to attact files */
    public $grid_reload = 0;
    /** ID of GridView will reload */
    public $grid_reload_id = '#list-files-grid';

    public $maxSize = 20;

    public $minSize = 1;

    public $maxWidth = 1000;

    public function init(): void
    {
        parent::init();
        $this->attact_model = json_encode($this->attact_model);
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

            .table * {
                vertical-align: middle !important;
            }
        CSS;

        \Yii::$app->view->registerCss($css);
        Yii::$app->view->registerJs(<<<JS
        onPjaxReady((root) => {


        
            var id_{$this->random} = 0;

            function generateRandomString(length) {
                const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = '';
                for (let i = 0; i < length; i++) {
                    const randomIndex = Math.floor(Math.random() * characters.length);
                    result += characters[randomIndex];
                }
                return result;
            }

            function el(id){
                return document.getElementById(id);
            }

            var count = 0;
            var uploading = 0;
            var total_files;
            let temp_image;
            let file_input = el("file-input-{$this->random}");
            let table_files = el('table-files-{$this->random}');
            let input_container = el('input-{$this->random}');
            let upload_button = el('upload-button-{$this->random}');
            let removeList = [];
            let filesArray = new Map();

            /**
             * Compress an image to be smaller than the max file size using Canvas API.
             * @param {File} file - The image file to compress.
             * @param {number} maxSize - The maximum file size in bytes.
             * @param {number} minSize - The maximum file size in bytes.
             * @returns {Promise<File>} A promise that resolves with the compressed image file.
             */
            function compressImage(file, index) {
                return new Promise((resolve, reject) => {

                    if (file.size <= {$this->minSize} * 1024 * 1024) {
                        console.log("Not compressed!");
                        resolve(file); // If the file is smaller than or equal to 1MB, return the original file
                        return;
                    }
                    console.log("Compressed!");
                    const reader = new FileReader();

                    // Load the image file
                    reader.readAsDataURL(file);
                    reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;

                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // Set canvas dimensions to the image dimensions
                        let width = img.width;
                        let height = img.height;

                        // Scale down the image dimensions if needed
                        const maxDimension = {$this->maxWidth}; // Max dimension (width or height) after scaling
                        if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.floor((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.floor((width * maxDimension) / height);
                            height = maxDimension;
                        }
                        }

                        // Set the canvas size and draw the scaled image
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        // Get the compressed image data
                        canvas.toBlob((blob) => {

                        if (blob.size > {$this->maxSize} * 1024 * 1024) {
                            reject(new Error('Image exceeds 5MB limit even after compression.'));
                        } else {
                            // Ensure the compressed image size is below the maxSize
                            const compressedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now()
                            });
                            resolve(compressedFile); // Return the compressed file
                        }

                        }, file.type, 0.8); // Adjust the quality (0.8 is 80%)
                    };

                    img.onerror = (error) => {
                        reject('Error loading image');
                    };
                    };

                    reader.onerror = (error) => {
                        reject('Error reading file' );
                    };
                });
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                var k = 1024; // Define the constant for kilobyte
                var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB']; // Units
                var i = Math.floor(Math.log(bytes) / Math.log(k)); // Determine the unit index
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function isImage(file){
                var fileType = file.type;
                var validImageTypes = ["image/jpeg", "image/png", "image/gif", "image/bmp", "image/webp"];
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

            function removeFile(index) {
                filesArray.delete(index);
                if(el("row_" + index) != null)
                    el("row_" + index).remove();
            }

            function upload(index,multiple){

                var i = index;
                var file = filesArray.get(index);
                var progressBar = el(`progress-bar-\${index}-{$this->random}`);
                progressBar.style.width = '0%';
                var uploadButton = el(`btn-upload-\${index}-{$this->random}`);
                var removeButton = el(`btn-remove-\${index}-{$this->random}`);

                var formData = new FormData();
                var descriptionInput = el(`row_\${index}`).querySelector(`input[name="description-\${index}"]`);
                var description = descriptionInput ? descriptionInput.value : '';

                formData.append('description', description);
                formData.append('file', file);
                formData.append('folder_id', $this->folder_id);
                formData.append('group_id', $this->group_id);
                formData.append('attact_model',JSON.stringify($this->attact_model));
                formData.append('thumb_aspect', "{$this->thumb_aspect}");
                formData.append('save', 1);

                let button = $(`#btn-upload-\${index}-{$this->random}`);
                let old_class = button.children("i").attr('class');
                button.prop('disabled',true);
                removeButton.disabled = true;
                object = button.children("i");
                object.removeClass(old_class);
                object.addClass('fas fa-sync fa-spin m-2');

                axios.post('/rest/storage/send', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Authorization': `Bearer {$this->token}`
                    },
                    onUploadProgress: (progressEvent) => {
                        var percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        progressBar.style.width = `\${percentCompleted}%`;
                        if(percentCompleted == 100){
                            progressBar.textContent  = 'processing... await...';                        
                        } else {
                            progressBar.textContent  = progressBar.style.width;                        
                        }
                        
                    }
                })
                .then((response) => {
                    if(response.data.success){
                        toastr.success(`File \${response.data.data.description} sended!`);
                        if(!multiple){
                            el("row_" + i).remove();
                        } else {
                            count++;
                            if(count == total_files){
                                file_input.value = '';
                            }
                            el("row_" + i).remove();
                        }
                        
                    }else{
                        
                        var send_error = response.data.data;
                        var erros = '';
                        if(send_error.file) {
                            Object.keys(send_error.file).forEach(key => {
                                erros += send_error.file[key];
                            });
                        } else {
                            erros = 'unknown error';
                        }
                        progressBar.style.width = `0%`;
                        progressBar.textContent  = `0%`;   
                        toastr.error(`Error on send file: \${erros}! `); 
                        uploadButton.disabled = false;
                        removeButton.disabled = false;
                    }

                    if({$this->grid_reload} == 1){
                        $.pjax.reload({container: "{$this->grid_reload_id}", async: true,timeout : false});
                    }
                })
                .catch(error => {
                    toastr.error("Error on page! " + error);
                    progressBar.style.width = 0;
                })
                .finally((response) => {
                    
                    if(uploading > 0){
                        uploading--;
                    }
                    if(uploading == 0){
                        input_container.style.display = 'block';
                    }
                        
                    progressBar.textContent = 0;
                    button.prop('disabled',false);
                    removeButton.disabled = false;
                    object.removeClass('fas fa-sync fa-spin m-2');
                    object.attr('class',old_class);
                });
            }

            file_input.addEventListener('change', async function(event) {

                var files = event.target.files;
                total_files = files.length;
                filesArray = new Map();
                
                if (files.length > 0) {
                    
                    upload_button.disabled = false;
                    table_files.innerHTML = '';

                    Array.from(file_input.files).forEach(async (file, index) => {

                        index = generateRandomString(8);
                        let upload_button = document.createElement("button");
                        upload_button.id = `btn-upload-\${index}-{$this->random}`;
                        upload_button.classList.add('btn', 'btn-warning');
                        upload_button.innerHTML = '<i class="fas fa-upload m-2"></i>';

                        upload_button.onclick = function() {
                            upload(index,false);
                        };

                        var remove_button = document.createElement('button');
                        remove_button.id = `btn-remove-\${index}-{$this->random}`;
                        remove_button.classList.add('btn', 'btn-danger');
                        remove_button.innerHTML = '<i class="fas fa-trash m-2"></i>';
                        remove_button.onclick = function() {
                            removeFile(index);
                        };

                        let progress_container = document.createElement("div");
                        progress_container.classList.add('progress');
                        progress_container.style.width = '300px';
                        
                        let progress_bar = document.createElement("div");
                        progress_bar.id = `progress-bar-\${index}-{$this->random}`;
                        progress_bar.classList.add('progress-bar', 'progress-bar-striped', 'bg-success', 'progress-bar-animated');
                        progress_container.append(progress_bar);

                        let preview = document.createElement("img");
                        preview.style.width = '100px';

                        if(isImage(file)){
                            await encodeImageFileAsURL(file,preview);
                        }else{
                            preview.src = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW';
                        }

                        var row = table_files.insertRow();
                        var cellImage =    row.insertCell(0);
                        var cellName =     row.insertCell(1);
                        var cellProgress = row.insertCell(2);
                        var cellSize =     row.insertCell(3);
                        var cellType =     row.insertCell(4);
                        var cellDescription = row.insertCell(5);
                        var cellActions = row.insertCell(6);

                        let descInput = document.createElement('input');
                        descInput.type = 'text';
                        descInput.classList.add('form-control');
                        descInput.placeholder = 'Descrição do arquivo';
                        descInput.name = `description-\${index}`;
                        cellDescription.appendChild(descInput);

                        row.id = "row_" + index;

                        cellProgress.append(progress_container);

                        cellImage.append(preview);
                        cellName.textContent = file.name;
                        cellName.classList.add('align-middle');
                        cellSize.textContent = formatFileSize(file.size); // Convert size to KB
                        cellType.textContent = file.type || 'N/A'; // Handle cases where type is unavailable
                        cellActions.append(upload_button);
                        cellActions.append(remove_button);


                        if(isImage(file)){
                            await compressImage(file,index).then((blob) => {
                                var file_compressed = new File([blob], file.name , { type: file.type, lastModified: new Date().getTime() });
                                let container = new DataTransfer();
                                container.items.add(file_compressed);
                                filesArray.set(index, container.files[0]);
                            }).catch((error) => {
                                filesArray.set(index, null);
                                removeFile(index);
                                toastr.error("Imagem Inválida! " + file.name);
                            });
                        }else{
                            filesArray.set(index,file);
                        }
                        
                    });
                    table_files.classList.remove('d-none');
                    file_input.value = '';
                }
                
            });

            el('upload-button-{$this->random}').addEventListener('click', (e) => {
                count = 0;
                el('upload-button-{$this->random}').disabled = true;
                input_container.style.display = 'none';
                uploading = filesArray.size;
                filesArray.forEach((file, index) => {
                    
                    if(isImage(file)){
                        compressImage(file,index).then((blob) => {
                    
                            var file_compressed = new File([blob], file.name , { type: file.type, lastModified: new Date().getTime() });
                            let container = new DataTransfer();
                            container.items.add(file_compressed);
                            upload(index,false);
                            return true;

                        }).catch((error) => {
                            alert(error);
                            return false;
                        });
                    }else{
                        upload(index,false);
                    }
                });

            });
        });
        JS);

        $select_file_text = Yii::t('app', 'Select file(s) to upload');
        $form_upload = <<< HTML

            <div class="btn-group mt-2" role="group" id="input-{$this->random}">
                <button class="btn btn-info position-relative">
                    <input type="file" multiple="true" class="position-absolute z-0 opacity-0 w-100 h-100"  id="file-input-{$this->random}" aria-label="Upload">
                    <i class="fas fa-folder-open m-2"></i> $select_file_text
                </button>
                <button class="btn btn-warning" id="upload-button-{$this->random}" disabled="true"> <i class="fas fa-upload m-2"></i> Upload</button>
            </div>

            <table class="table" id="table-files-{$this->random}">
                <tbody>
                </tbody>
            </table>
        HTML;
        echo $form_upload;
    }
}
