<?php

namespace croacworks\essentials\widgets;

use croacworks\essentials\themes\coreui\assets\PluginAsset;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * StorageImage
 *
 * Widget de upload e edição (crop) de **imagens** com preview, CropperJS e
 * compressão client-side, integrado ao novo stack de storage:
 * `/storage/upload`, `/storage/info`, `/storage/delete`, `/storage/attach`.
 *
 * ## Modos de operação
 * - **defer** (padrão): não envia nada imediatamente.
 *   - Após cortar, o arquivo é **injetado** no `<input type="file" name="Model[attribute]">`
 *     do formulário. A gravação efetiva ocorre no submit, via `AttachFileBehavior`
 *     (que chama `StorageService->upload()`).
 * - **instant**: envia o arquivo **na hora** para `/storage/upload` (usa `StorageService`),
 *   preenche um `hidden` com o `file_id` retornado e atualiza o preview.
 *   Opcionalmente remove o arquivo antigo e/ou cria pivot via `/storage/attach`.
 *
 * ## Endpoints usados (controller fino)
 * - `POST /storage/upload`         → retorna `{ ok: true, data: File }`
 * - `POST /storage/delete?id=ID`   → remove arquivo e thumb
 * - `POST /storage/attach`         → cria ligação pivô {class_name, model_id, file_id, ...}
 *
 * ## Eventos JavaScript
 * - `uploadImage:pending` — modo **defer**: emitido após o crop e injeção do arquivo no form.
 * - `uploadImage:saved`   — modo **instant**: emitido após upload ok, com `{ file }` no `detail`.
 *
 * ## Exemplo (defer)
 * ```php
 * // campo file oculto do AR
 * echo $form->field($model, 'file_id')->fileInput([
 *   'accept' => 'image/*', 'style' => 'display:none'
 * ])->label(false);
 *
 * // widget
 * echo \croacworks\essentials\widgets\StorageImage::widget([
 *   'mode'        => 'defer',
 *   'model'       => $model,
 *   'attribute'   => 'file_id',
 *   'imageUrl'    => $model->file->url ?? '',
 *   'aspectRatio' => '16/9',
 * ]);
 * ```
 *
 * ## Exemplo (instant)
 * ```php
 * echo \croacworks\essentials\widgets\StorageImage::widget([
 *   'mode'               => 'instant',
 *   'model'              => $model,           // com PK
 *   'attribute'          => 'file_id',
 *   'imageUrl'           => $model->file->url ?? '',
 *   'aspectRatio'        => '1',
 *   'deleteOldOnReplace' => true,             // apaga antigo via /storage/delete
 *   // pivot opcional:
 *   // 'attactModelClass'  => \common\models\PostFile::class,
 *   // 'attactModelFields' => ['model_id','file_id'],
 * ]);
 * ```
 *
 * ## Observações
 * - O botão **Remover** limpa preview/inputs e marca um hidden `remove=1` para o
 *   `AttachFileBehavior` cuidar no submit (não deleta no servidor imediatamente).
 * - Requer o asset com CropperJS via `PluginAsset::add(['cropper'])`.
 * - Compatível com CSRF (mesma origem); o widget injeta o token no `FormData`.
 *
 * @package croacworks\essentials\widgets
 * @since   1.0
 *
 * @property-read string $id Identificador único do widget gerado.
 *
 * @see \croacworks\essentials\components\StorageService
 * @see \croacworks\essentials\controllers\StorageController
 * @see \croacworks\essentials\behaviors\AttachFileBehavior
 */

class StorageImage extends \yii\bootstrap5\Widget
{
  /** Preview inicial */
  public string $imageUrl = '';
  public string $accept = 'image/*';

  /** '1', '16/9' ou 'NaN' (livre) */
  public string $aspectRatio = '1';

  /** Compressão client-side */
  public int $maxWidth = 1200;
  public float $maxSizeMB = 3.0;

  /** Modo de envio: 'defer' (padrão) ou 'instant' */
  public string $mode = 'defer';

  /** Esconder o botão "Salvar" do modal? (no 'defer' o "Cortar" já injeta e fecha) */
  public bool $hideSaveButton = true;

  /** Endpoints (modo instant) — agora apontando para o controller novo */
  public $sendUrl = ['/storage/upload'];

  /** Associação com o modelo/atributo (para descobrir name/id corretos) */
  public $model = null;               // \yii\db\ActiveRecord|null
  public string $attribute = 'file_id';

  /** (Opcional) se quiser forçar o id do input file do modelo (ex.: 'captive-file_id') */
  public ?string $fileInputId = null;

  /** (Opcional) pivot via attact_model (usado no 'instant') */
  public ?string $attactModelClass = null;
  public array $attactModelFields = []; // ex.: ['model_id','file_id']

  /** No upload instant, pós-processar */
  public bool $linkModelOnSend = true;        // mantém o hidden atualizado
  public bool $deleteOldOnReplace = true;     // deleta o arquivo antigo via /storage/delete

  /** Parâmetros de envio */
  public int $folderId = 2;
  public int $groupId  = 1;
  public $thumbAspect = 1;  // 1 ou "L/H" (ex.: "160/99")
  public int $quality  = 85;

  /** Labels */
  public string $labelSelect = 'Selecione a imagem';
  public string $labelCrop   = 'Cortar';
  public string $labelSave   = 'Salvar';
  public string $labelCancel = 'Cancelar';
  public string $labelRemove = 'Remover';

  public string $placeholder = '/dummy/code.php?x=250x250/fff/000.jpg&text=NO IMAGE';

  /** Integração com o AttachFileBehavior (remoção no submit) */
  public string $removeFlagParam  = 'remove';
  public bool   $removeFlagScoped = false; // true => envia como Model[remove]

  public function init(): void
  {
    parent::init();
    PluginAsset::register(Yii::$app->view)->add(['cropper']);

    if ($this->model !== null && !method_exists($this->model, 'hasAttribute')) {
      throw new InvalidConfigException('Parâmetro $model deve ser um ActiveRecord ou null.');
    }
    if (!in_array($this->mode, ['defer', 'instant'], true)) {
      throw new InvalidConfigException('Parâmetro $mode deve ser "defer" ou "instant".');
    }
  }

  public function run(): string
  {
    $view = $this->getView();
    $id = $this->getId();

    $wrapId    = "uii_wrap_{$id}";
    $photoId   = "uii_photo_{$id}";
    $imgId     = "uii_img_{$id}";
    $inputId   = "uii_input_{$id}";
    $modalId   = "uii_modal_{$id}";
    $cropId    = "uii_crop_{$id}";
    $saveId    = "uii_save_{$id}";
    $cancelId  = "uii_cancel_{$id}";
    $removeBtn = "uii_removebtn_{$id}";
    $overlayId = "uii_overlay_{$id}";

    $initialUrl = $this->imageUrl ?: $this->placeholder;

    // CSRF
    $csrfParam = Yii::$app->request->csrfParam;
    $csrfToken = Yii::$app->request->getCsrfToken();

    //MODEL FILE
    $haveModel  = $this->model !== null && $this->model->hasAttribute($this->attribute);
    $modelClass = $haveModel ? addslashes(get_class($this->model)) : '';
    $modelId    = $haveModel ? (string)$this->model->getPrimaryKey() : '';

    // URLs
    $sendUrl = Url::to($this->sendUrl);
    $deleteUrl = Url::to(['/storage/delete']);
    $attachUrl = Url::to(['/storage/attach']);

    // Nome/ID do input file do modelo
    $inputName   = $haveModel ? Html::getInputName($this->model, $this->attribute) : 'file_id';
    $inputIdPhp  = $this->fileInputId ?: ($haveModel ? Html::getInputId($this->model, $this->attribute) : 'uii_file_' . $id);

    // Hidden de remoção (Behavior)
    $removeHiddenId = "uii_remove_{$id}";
    $removeHiddenName = $this->removeFlagScoped && $haveModel
      ? Html::getInputName($this->model, $this->removeFlagParam)
      : $this->removeFlagParam;

    // attact_model opcional
    $attactClass  = $this->attactModelClass ? addslashes($this->attactModelClass) : '';
    $attactFields = $this->attactModelFields;

    // Opções JS
    $aspect  = $this->aspectRatio;
    $maxW    = (int)$this->maxWidth;
    $maxMB   = (float)$this->maxSizeMB;
    $folder  = (int)$this->folderId;
    $group   = (int)$this->groupId;
    $thumb   = is_numeric($this->thumbAspect) ? (int)$this->thumbAspect : "'{$this->thumbAspect}'";
    $quality = (int)$this->quality;

    $mode              = $this->mode;
    $hideSaveButton    = $this->hideSaveButton ? 'true' : 'false';

    $linkOnSend        = $this->linkModelOnSend ? 'true' : 'false';
    $deleteOld         = $this->deleteOldOnReplace ? '1' : '0';

    // ===== CSS =====
    $css = <<<CSS
.uploader-card .overlay{
  position:absolute; inset:0; display:none; align-items:center; justify-content:center; z-index:1055;
  background:rgba(0,0,0,.35);
}
.uploader-card .preview{ max-width:600px; }
.uploader-modal .modal-dialog{ max-width:min(95vw, 1200px); margin:1rem auto; }
.uploader-modal .modal-content{ max-height:92vh; }
.uploader-modal .modal-body{ padding:0; overflow:hidden; }
.uploader-modal .img-container{
  position:relative; width:100%; height:calc(92vh - 140px);
  background: conic-gradient(#eee 0 25%, #ddd 0 50%) 0 / 20px 20px;
}
.uploader-modal .img-container img{ display:block; max-width:100%; }
.btn-group .btn, .btn-group label.btn { padding-top: .5rem; padding-bottom: .5rem; }
CSS;

    // ===== JS =====
    $script = <<<JS
(function(){
  const wrap    = document.getElementById('$wrapId');
  const photo   = document.getElementById('$photoId');
  const imageEl = document.getElementById('$imgId');
  const input   = document.getElementById('$inputId');
  const overlay = document.getElementById('$overlayId');

  const btnCrop   = document.getElementById('$cropId');
  const btnSave   = document.getElementById('$saveId'); btnSave.style.display='none';
  const btnCancel = document.getElementById('$cancelId');
  const btnRemove = document.getElementById('$removeBtn');

  const removeHidden = document.getElementById('$removeHiddenId');
  function setRemoveFlag(v){ if (removeHidden) removeHidden.value = String(v); }

  const modalEl = document.getElementById('$modalId');
  const modal = new bootstrap.Modal(modalEl, {backdrop:'static', keyboard:false});

  const MODE = '{$mode}';
  const HIDE_SAVE_BTN = {$hideSaveButton};

  const MODEL_CLASS = '{$modelClass}';
  const MODEL_ID    = '{$modelId}';
  const LINK_ON_SEND= {$linkOnSend};
  const DELETE_OLD  = {$deleteOld};

  const MODEL_INPUT_ID   = '{$inputIdPhp}';
  const MODEL_INPUT_NAME = '{$inputName}';

  const CSRF_PARAM = '{$csrfParam}';
  const CSRF_TOKEN = '{$csrfToken}';

  const MAX_W = {$maxW};
  const MAX_MB = {$maxMB};
  const MAX_BYTES = MAX_MB * 1024 * 1024;
  const ASPECT = (function(){ try { return eval('{$aspect}'); } catch(e){ return NaN; }})();

  const SEND_URL   = '{$sendUrl}';
  const DELETE_URL = '{$deleteUrl}';
  const ATTACH_URL = '{$attachUrl}';

  const FOLDER_ID    = {$folder};
  const GROUP_ID     = {$group};
  const THUMB_ASPECT = {$thumb};
  const QUALITY      = {$quality};

  const attactClass  = '{$attactClass}';
  const attactFields = JSON.parse('{$this->jsonSafe($attactFields)}');

  function showOverlay(){ overlay.style.display='flex'; }
  function hideOverlay(){ overlay.style.display='none'; }

  function ensureModelFileInput() {
    let el = document.getElementById(MODEL_INPUT_ID);
    if (el && el.type === 'file') return el;

    el = document.querySelector(`input[type="file"][name="\${CSS.escape(MODEL_INPUT_NAME)}"]`);
    if (el) return el;

    const form = wrap.closest('form');
    if (!form) return null;
    el = document.createElement('input');
    el.type = 'file';
    el.name = MODEL_INPUT_NAME;
    el.id = MODEL_INPUT_ID;
    el.style.display = 'none';
    form.appendChild(el);
    return el;
  }
  const modelFileInput = (MODE === 'defer') ? ensureModelFileInput() : null;

  // No instant, mantemos um hidden com o mesmo name do atributo
  let hidden = null;
  if (MODE === 'instant') {
    hidden = document.querySelector(`input[type="hidden"][name="\${CSS.escape(MODEL_INPUT_NAME)}"]`);
    if (!hidden) {
      const form = wrap.closest('form');
      if (form) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = MODEL_INPUT_NAME;
        hidden.value = '';
        form.appendChild(hidden);
      }
    }
    // evita colisão com um input file existente de mesmo name
    const fileSameName = document.querySelector(`input[type="file"][name="\${CSS.escape(MODEL_INPUT_NAME)}"]`);
    if (fileSameName) { fileSameName.name = MODEL_INPUT_NAME + '__ignore'; }
  }

  function isImage(file){ return ["image/jpeg","image/png","image/gif","image/bmp","image/webp"].includes(file.type); }

  function compressImage(file){
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = new Image();
        img.onload = () => {
          let w = img.width, h = img.height;
          if (w > MAX_W || h > MAX_W) {
            if (w > h) { h = Math.floor(h * MAX_W / w); w = MAX_W; }
            else { w = Math.floor(w * MAX_W / h); h = MAX_W; }
          }
          const canvas = document.createElement('canvas');
          canvas.width = w; canvas.height = h;
          canvas.getContext('2d').drawImage(img,0,0,w,h);
          canvas.toBlob((blob) => {
            if (!blob) return reject('Falha ao comprimir.');
            if (blob.size > MAX_BYTES) return reject('Imagem excede ' + MAX_MB + 'MB mesmo após compressão.');
            resolve(new File([blob], file.name, {type: file.type, lastModified: Date.now()}));
          }, file.type, 0.85);
        };
        img.onerror = () => reject('Erro ao carregar a imagem.');
        img.src = e.target.result;
      };
      reader.onerror = () => reject('Erro ao ler o arquivo.');
      reader.readAsDataURL(file);
    });
  }

  let tmpFile = null;
  let cropper = null;
  let lastSavedFileId = hidden?.value || null;

  function fitAndCenter() {
    if (!cropper) return;
    const cont = cropper.getContainerData();
    let w, h;
    if (Number.isFinite(ASPECT)) {
      w = Math.min(cont.width * 0.92, cont.height * 0.92 * ASPECT);
      h = w / ASPECT;
    } else {
      w = cont.width * 0.92;
      h = cont.height * 0.92;
    }
    cropper.setCropBoxData({ width:w, height:h, left:(cont.width-w)/2, top:(cont.height-h)/2 });
  }

  function assignFileToModelInput(file) {
    if (!modelFileInput) return false;
    const dt = new DataTransfer();
    dt.items.add(file);
    modelFileInput.files = dt.files;
    return true;
  }

  async function uploadFinalFile(blobOrFile){
    const fd = new FormData();
    const fileName = (tmpFile?.name || 'image.jpg');
    const file = (blobOrFile instanceof File) ? blobOrFile : new File([blobOrFile], fileName, {type: blobOrFile.type || 'image/jpeg', lastModified: Date.now()});
    fd.append('file', file);
    fd.append('save', '1');
    fd.append('folder_id', String(FOLDER_ID));
    fd.append('group_id', String(GROUP_ID));
    fd.append('thumb_aspect', String(THUMB_ASPECT));
    fd.append('quality', String(QUALITY));
    fd.append(CSRF_PARAM, CSRF_TOKEN);

    const res = await fetch(SEND_URL, { method:'POST', body: fd, credentials:'same-origin' });
    if(!res.ok) throw new Error('Falha no upload ('+res.status+').');
    const json = await res.json();
    if (!json || json.ok !== true || !json.data) {
      const msg = (json && (json.error || json.errors || json.data)) ? JSON.stringify(json.error || json.errors || json.data) : 'Resposta inválida.';
      throw new Error('Upload não aceito: ' + msg);
    }
    return json.data; // modelo File (attrs)
  }

  async function deleteOldOnServer(oldId){
    const fd = new FormData();
    fd.append(CSRF_PARAM, CSRF_TOKEN);
    const res = await fetch(DELETE_URL + '?id=' + encodeURIComponent(oldId), { method:'POST', body: fd, credentials:'same-origin' });
    // ignore falhas silenciosamente
    return true;
  }

  async function attachPivotIfRequested(fileId){
    if (!attactClass || !Array.isArray(attactFields) || attactFields.length !== 2 || !MODEL_ID) return;
    const fd = new FormData();
    fd.append('class_name', attactClass);
    fd.append('model_id', String(MODEL_ID));
    fd.append('file_id', String(fileId));
    fd.append('field_model_id', attactFields[0]);
    fd.append('field_file_id', attactFields[1]);
    fd.append(CSRF_PARAM, CSRF_TOKEN);
    try {
      await fetch(ATTACH_URL, { method:'POST', body: fd, credentials:'same-origin' });
    } catch(e) { /* silencioso */ }
  }

  // ---- Eventos ----
  input.addEventListener('change', async (e) => {
    const files = e.target.files;
    if(!files || !files.length) return;
    tmpFile = files[0];
    if (!isImage(tmpFile)) { alert('Arquivo inválido.'); return; }

    try{
      showOverlay();
      let toPreview;
      if (tmpFile.type !== 'image/png') {
        const compressed = await compressImage(tmpFile);
        toPreview = URL.createObjectURL(compressed);
        tmpFile = compressed;
      } else {
        toPreview = URL.createObjectURL(tmpFile);
      }
      imageEl.src = toPreview;
      btnSave.style.display = 'block';
      setRemoveFlag(0);
      modal.show();
    } catch(err){
      alert(err);
    } finally {
      hideOverlay();
    }
  });

  modalEl.addEventListener('shown.bs.modal', () => {
    if (cropper) { cropper.destroy(); cropper = null; }
    cropper = new Cropper(imageEl, {
      viewMode: 2,
      aspectRatio: ASPECT,
      initialAspectRatio: ASPECT,
      autoCropArea: 1,
      responsive: true,
      background: false,
      dragMode: 'move',
      zoomOnWheel: true,
      ready() { setTimeout(fitAndCenter, 0); }
    });
    if (HIDE_SAVE_BTN) btnSave?.classList.add('d-none');
  });

  window.addEventListener('resize', () => {
    if (modalEl.classList.contains('show')) setTimeout(fitAndCenter, 100);
  });

  btnCancel.addEventListener('click', () => modal.hide());

  // CORTAR
  btnCrop.addEventListener('click', async () => {
    if (!cropper) return;
    try{
      showOverlay();
      const canvas = cropper.getCroppedCanvas();
      const blob = await new Promise(res => canvas.toBlob(res, tmpFile?.type || 'image/jpeg', 0.9));
      if (!blob) throw new Error('Falha ao gerar recorte.');

      let finalFile = (tmpFile?.type === 'image/png')
        ? new File([blob], tmpFile.name, {type: blob.type})
        : await (async () => {
            const f = new File([blob], tmpFile?.name || 'image.jpg', {type: blob.type});
            return await compressImage(f);
          })();

      // preview sempre
      photo.src = URL.createObjectURL(finalFile);

      if (MODE === 'defer') {
        assignFileToModelInput(finalFile);
        setRemoveFlag(0);
        document.dispatchEvent(new CustomEvent('uploadImage:pending', { detail: { widgetId: '$id' }}));
      }
      btnSave.style.display = 'block';
      modal.hide();
    } catch (err){
      alert(err.message || err);
    } finally {
      hideOverlay();
    }
  });

  // SALVAR
  btnSave.addEventListener('click', async () => {
    if (!cropper) return;
    try{
      showOverlay();
      const canvas = cropper.getCroppedCanvas();
      const blob = await new Promise(res => canvas.toBlob(res, tmpFile?.type || 'image/jpeg', 0.9));
      if(!blob) throw new Error('Falha ao gerar recorte.');

      let finalFile = (tmpFile?.type === 'image/png')
        ? new File([blob], tmpFile.name, {type: blob.type})
        : await (async () => {
            const f = new File([blob], tmpFile?.name || 'image.jpg', {type: blob.type});
            return await compressImage(f);
          })();

      if (MODE === 'defer') {
        photo.src = URL.createObjectURL(finalFile);
        assignFileToModelInput(finalFile);
        setRemoveFlag(0);
        modal.hide();
        return;
      }

      // instant → envia para /storage/upload
      const saved = await uploadFinalFile(finalFile);
      const newId = saved.id || null;

      // preview
      if (saved.url) photo.src = saved.url + '?v=' + Date.now();

      // sincroniza hidden [Model][file_id]
      if (hidden) hidden.value = String(newId ?? '');

      // se solicitado, deleta o antigo no servidor
      if (DELETE_OLD && lastSavedFileId && Number(lastSavedFileId) !== Number(newId)) {
        try { await deleteOldOnServer(lastSavedFileId); } catch(e){}
      }
      lastSavedFileId = newId;

      // pivot opcional
      await attachPivotIfRequested(newId);

      setRemoveFlag(0);
      document.dispatchEvent(new CustomEvent('uploadImage:saved', { detail: { file: saved, widgetId: '$id' }}));
      modal.hide();
    } catch(err){
      console.error(err);
      alert(err.message || err);
    } finally {
      hideOverlay();
    }
  });

  // REMOVER — marca intenção no submit; não apaga servidor aqui
  btnRemove.addEventListener('click', () => {
    try{
      showOverlay();
      photo.src = '{$this->placeholder}';
      btnSave.style.display = 'none';
      if (modelFileInput) modelFileInput.value = '';
      if (hidden) hidden.value = '';
      setRemoveFlag(1);
    } finally { hideOverlay(); }
  });

})();
JS;

    $view->registerCss($css);
    $view->registerJs($script, \yii\web\View::POS_END);

    $showRemove = ($this->imageUrl !== '') ? '' : 'd-none';

    ob_start(); ?>
    <div id="<?= $wrapId ?>">
      <div class="card uploader-card">
        <div class="card-body position-relative">

          <div id="<?= $overlayId ?>" class="overlay">
            <div class="text-white d-flex align-items-center gap-2">
              <strong><?= Yii::t('app', 'Processing...') ?></strong>
              <div class="spinner-border ms-2" role="status" aria-hidden="true"></div>
            </div>
          </div>

          <div class="text-center pb-1">
            <img id="<?= $photoId ?>" class="rounded preview" src="<?= Html::encode($initialUrl) ?>" alt="preview">
          </div>

          <div class="text-center pb-2">
            <!-- input fora do label -->
            <input id="<?= $inputId ?>" type="file" accept="<?= Html::encode($this->accept) ?>" class="d-none">

            <!-- hidden de remoção para o Behavior -->
            <input type="hidden" id="<?= $removeHiddenId ?>" name="<?= Html::encode($removeHiddenName) ?>" value="0">

            <div class="btn-group" role="group" aria-label="upload actions">
              <label class="btn btn-primary mb-0" for="<?= $inputId ?>">
                <i class="fas fa-file-upload me-1"></i><?= Html::encode($this->labelSelect) ?>
              </label>

              <button type="button" id="<?= $saveId ?>" class="btn btn-primary">
                <i class="fas fa-save me-1"></i><?= Html::encode($this->labelSave) ?>
              </button>

              <button type="button" id="<?= $removeBtn ?>" class="btn btn-danger <?= $showRemove ?>">
                <i class="fas fa-trash me-1"></i><?= Html::encode($this->labelRemove) ?>
              </button>
            </div>
          </div>

        </div>
      </div>

      <div class="modal fade uploader-modal modal-fullscreen-sm-down" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true" aria-labelledby="<?= $modalId ?>_label">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 id="<?= $modalId ?>_label" class="modal-title">Cortar imagem</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Html::encode($this->labelCancel) ?>"></button>
            </div>
            <div class="modal-body">
              <div class="img-container">
                <img id="<?= $imgId ?>" src="<?= Html::encode($this->placeholder) ?>" alt="crop">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" id="<?= $cropId ?>" class="btn btn-success">
                <i class="fas fa-crop"></i> <?= Html::encode($this->labelCrop) ?>
              </button>
              <button type="button" id="<?= $cancelId ?>" class="btn btn-secondary" data-bs-dismiss="modal">
                <?= Html::encode($this->labelCancel) ?>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php
    return ob_get_clean();
  }

  private function jsonSafe($data): string
  {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return addslashes($json ?? '[]');
  }
}
