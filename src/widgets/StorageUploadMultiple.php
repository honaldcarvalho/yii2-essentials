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
        PluginAsset::register(Yii::$app->view)->add(['axios', 'jquery-cropper']);
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
(function boot_{$this->random}(){
    // Garante re-execução após PJAX
    document.addEventListener('pjax:end', boot_{$this->random}, { once: true });

    // Evita dupla inicialização
    if (window['__init_storage_{$this->random}']) return;
    window['__init_storage_{$this->random}'] = true;

    function generateRandomString(length){
        const chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let out=''; for(let i=0;i<length;i++) out+=chars[Math.floor(Math.random()*chars.length)];
        return out;
    }
    function el(id){ return document.getElementById(id); }
    function isImage(file){
        return ["image/jpeg","image/png","image/gif","image/bmp","image/webp"].includes(file?.type||"");
    }
    function formatFileSize(bytes){
        if (!bytes) return '0 Bytes';
        const k=1024, sizes=['Bytes','KB','MB','GB','TB']; 
        const i=Math.floor(Math.log(bytes)/Math.log(k));
        return (bytes/Math.pow(k,i)).toFixed(2)+' '+sizes[i];
    }
    async function encodeImageFileAsURL(file,preview){
        return new Promise((res,rej)=>{
            const r=new FileReader();
            r.onloadend=()=>{ preview.src=r.result; res(); };
            r.onerror=rej; r.readAsDataURL(file);
        });
    }

    // ---- variáveis do widget ----
    let count = 0, uploading = 0, total_files = 0;
    const file_input = el("file-input-{$this->random}");
    const table_files = el("table-files-{$this->random}");
    const input_container = el("input-{$this->random}");
    const upload_button = el("upload-button-{$this->random}");
    const filesArray = new Map();

    async function compressImage(file, index){
        return new Promise((resolve,reject)=>{
            const minBytes = {$this->minSize} * 1024 * 1024;
            const maxBytes = {$this->maxSize} * 1024 * 1024;
            const maxDimension = {$this->maxWidth};

            if (file.size <= minBytes) return resolve(file);

            const reader = new FileReader();
            reader.onload = e => {
                const img = new Image();
                img.onload = () => {
                    let w = img.width, h = img.height;
                    if (w>maxDimension || h>maxDimension){
                        if (w>h){ h = Math.floor(h*maxDimension/w); w = maxDimension; }
                        else    { w = Math.floor(w*maxDimension/h); h = maxDimension; }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d'); ctx.drawImage(img,0,0,w,h);
                    canvas.toBlob(blob=>{
                        if(!blob) return reject(new Error('Falha ao comprimir.'));
                        if (blob.size > maxBytes) return reject(new Error('Imagem excede o limite após compressão.'));
                        resolve(new File([blob], file.name, { type: file.type, lastModified: Date.now() }));
                    }, file.type, 0.8);
                };
                img.onerror = ()=>reject(new Error('Erro carregando imagem.'));
                img.src = e.target.result;
            };
            reader.onerror = ()=>reject(new Error('Erro lendo arquivo.'));
            reader.readAsDataURL(file);
        });
    }

    function removeFile(index){
        filesArray.delete(index);
        const row = el("row_"+index);
        if (row) row.remove();
    }

    function upload(index, multiple){
        const file = filesArray.get(index);
        if (!file) return;
        const bar = el(`progress-bar-\${index}-{$this->random}`);
        const btn = $(`#btn-upload-\${index}-{$this->random}`);
        const removeBtn = el(`btn-remove-\${index}-{$this->random}`);

        let oldClass = btn.children("i").attr("class");
        btn.prop("disabled", true);
        removeBtn.disabled = true;
        let icon = btn.children("i").removeClass(oldClass).addClass("fas fa-sync fa-spin m-2");

        const fd = new FormData();
        const descInput = el(`row_\${index}`)?.querySelector(`input[name="description-\${index}"]`);
        fd.append('description', descInput ? descInput.value : '');
        fd.append('file', file);
        fd.append('folder_id', {$this->folder_id});
        fd.append('group_id', {$this->group_id});
        // já vem json_encode do PHP → NÃO usar JSON.stringify aqui!
        fd.append('attact_model', {$this->attact_model});
        fd.append('thumb_aspect', "{$this->thumb_aspect}");
        fd.append('save', 1);

        bar.style.width = '0%'; bar.textContent = '';

        axios.post('/rest/storage/send', fd, {
            headers: { 'Content-Type': 'multipart/form-data', 'Authorization': 'Bearer {$this->token}' },
            onUploadProgress: (e)=>{
                const total = e.total || 0;
                if (!total) return; // evita NaN%
                const pct = Math.round((e.loaded*100)/total);
                bar.style.width = pct+'%';
                bar.textContent = (pct===100? 'processando...' : pct+'%');
            }
        }).then((resp)=>{
            if (resp.data?.success){
                toastr.success(`Arquivo \${resp.data.data?.description||''} enviado!`);
                if (!multiple) removeFile(index);
                else {
                    count++; if (count===total_files) file_input.value = '';
                    removeFile(index);
                }
                if ({$this->grid_reload}==1){
                    $.pjax?.reload?.({ container: "{$this->grid_reload_id}", async: true, timeout: false });
                }
            } else {
                const send_error = resp.data?.data || {};
                let erros = '';
                if (send_error.file){
                    Object.keys(send_error.file).forEach(k=>erros += send_error.file[k]);
                } else { erros = 'erro desconhecido'; }
                bar.style.width = '0%'; bar.textContent = '0%';
                toastr.error(`Erro ao enviar: \${erros}`);
                btn.prop("disabled", false); removeBtn.disabled = false;
            }
        }).catch((err)=>{
            toastr.error("Erro na página: " + err);
            bar.style.width = '0%'; bar.textContent = '';
        }).finally(()=>{
            if (uploading>0) uploading--;
            if (uploading===0) input_container.style.display = 'block';
            btn.prop("disabled", false);
            removeBtn.disabled = false;
            icon.removeClass('fas fa-sync fa-spin m-2').attr('class', oldClass);
        });
    }

    // input change
    file_input?.addEventListener('change', async (ev)=>{
        const files = Array.from(ev.target.files||[]);
        total_files = files.length;
        filesArray.clear();

        if (!files.length) return;
        upload_button.disabled = false;
        const tbody = table_files.tBodies[0] || table_files.createTBody();
        tbody.innerHTML = '';

        for (const originalFile of files){
            const index = generateRandomString(8);

            // preview
            const preview = document.createElement('img');
            preview.style.width = '100px';

            if (isImage(originalFile)){
                try{ await encodeImageFileAsURL(originalFile, preview); }
                catch{ preview.src = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW'; }
            } else {
                preview.src = '/dummy/code.php?x=150x150/fff/000.jpg&text=NO PREVIEW';
            }

            // linha
            const row = tbody.insertRow();
            row.id = "row_"+index;
            const cImg = row.insertCell();
            const cName = row.insertCell();
            const cProg = row.insertCell();
            const cSize = row.insertCell();
            const cType = row.insertCell();
            const cDesc = row.insertCell();
            const cAct  = row.insertCell();

            cImg.append(preview);
            cName.textContent = originalFile.name; cName.classList.add('align-middle');

            const progWrap = document.createElement('div'); progWrap.className='progress'; progWrap.style.width='300px';
            const progBar  = document.createElement('div');
            progBar.id = `progress-bar-\${index}-{$this->random}`;
            progBar.className='progress-bar progress-bar-striped bg-success progress-bar-animated';
            progWrap.append(progBar);
            cProg.append(progWrap);

            cSize.textContent = formatFileSize(originalFile.size);
            cType.textContent = originalFile.type || 'N/A';

            const desc = document.createElement('input');
            desc.type='text'; desc.className='form-control'; desc.value = originalFile.name;
            desc.placeholder='Descrição do arquivo'; desc.name=`description-\${index}`;
            cDesc.append(desc);

            const btnUp = document.createElement('button');
            btnUp.id = `btn-upload-\${index}-{$this->random}`;
            btnUp.className = 'btn btn-warning'; btnUp.innerHTML = '<i class="fas fa-upload m-2"></i>';
            btnUp.addEventListener('click', ()=>upload(index,false));

            const btnRm = document.createElement('button');
            btnRm.id = `btn-remove-\${index}-{$this->random}`;
            btnRm.className = 'btn btn-danger'; btnRm.innerHTML = '<i class="fas fa-trash m-2"></i>';
            btnRm.addEventListener('click', ()=>removeFile(index));

            cAct.append(btnUp); cAct.append(btnRm);

            if (isImage(originalFile)){
                try{
                    const compressed = await compressImage(originalFile, index);
                    // DataTransfer para manter tipo File
                    const dt = new DataTransfer(); dt.items.add(compressed);
                    filesArray.set(index, dt.files[0]);
                }catch(e){
                    filesArray.set(index, null); removeFile(index);
                    toastr.error("Imagem inválida: " + originalFile.name);
                }
            } else {
                filesArray.set(index, originalFile);
            }
        }
        table_files.classList.remove('d-none');
        file_input.value = ''; // limpa para permitir mesma seleção novamente
    });

    upload_button?.addEventListener('click', ()=>{
        count = 0; upload_button.disabled = true;
        input_container.style.display = 'none';
        uploading = filesArray.size;
        filesArray.forEach((file, index)=>{
            if (isImage(file)){
                // já comprimido no add; não recomprimir aqui
                upload(index,false);
            } else {
                upload(index,false);
            }
        });
    });
})(); // fim boot
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
