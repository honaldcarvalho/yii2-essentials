<?php

namespace croacworks\essentials\widgets;

use croacworks\essentials\themes\coreui\assets\PluginAsset;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * UploadImageInstant — Upload widget with preview, CropperJS, and client-side compression.
 *
 * HOW IT WORKS
 * ─────────────────
 * • mode = 'defer'  (default)
 *   - Does not upload immediately.
 *   - After cropping, the file is INJECTED into the form's <input type="file" name="Model[file_id]">.
 *   - When the form is submitted, your Behavior/logic on the server uploads it, stores file_id, and removes the old one.
 *
 * • mode = 'instant'
 *   - Uploads immediately to /rest/storage/send (StorageController::actionSend).
 *   - With the updated StorageController it can LINK the file to your model by sending:
 *       model_class, model_id, model_field and (optional) delete_old=1
 *   - The widget also fills a <input type="hidden" name="Model[file_id]"> with the returned id as a fallback.
 *
 * AUTHENTICATION (instant mode)
 * ─────────────────────────────
 * The widget sends Authorization: Bearer {token}, trying in this order:
 *   1) <meta name="api-token" content="...">
 *   2) PHP property $authToken
 *   3) localStorage['token']
 *   4) window.AUTH_TOKEN
 *   5) cookie "token"
 * If none is found and authQueryFallback=true, it appends ?access-token= to the URL.
 *
 * MAIN PARAMETERS
 * ───────────────
 * • mode: 'defer' | 'instant'              (default: 'defer')
 * • model: ActiveRecord (optional, recommended)
 * • attribute: string                      (e.g., 'file_id')
 * • imageUrl: string                       (initial preview URL)
 * • aspectRatio: '1' | '16/9' | 'NaN'      (NaN = free)
 * • maxWidth (px), maxSizeMB (MB)          (client-side compression)
 * • sendUrl                                (instant mode; default: /rest/storage/send)
 * • linkModelOnSend: bool                  (instant → send model_* to link on the server)
 * • deleteOldOnReplace: bool               (instant → delete old file on server when replacing)
 * • attactModelClass, attactModelFields    (optional; creates pivot via attach_model along with the upload)
 * • removeFlagParam, removeFlagScoped      (AttachFileBehavior integration for removal on submit)
 * • authToken / meta api-token / storage 'token' / cookie 'token'
 *
 * NEW (Front-end i18n + JS callback)
 * ──────────────────────────────────
 * • Front-end translations via `yii.t('app', '...')` in JavaScript (no Yii::t in PHP).
 *   If `yii.t` is not available, English text is used as a fallback.
 *
 * • callBackOnSelect: string|null
 *   Set the name of a global JS function to receive a payload when the user:
 *     - selects a file (phase: "selected")
 *     - crops a file (phase: "cropped")
 *     - successfully saves (instant mode) (phase: "saved")
 *
 *   Signature:
 *     function yourFn({ phase, widgetId, file, fileId, url }) { ... }
 *
 *   Phases and payload:
 *     - "selected": { phase, widgetId, file }
 *     - "cropped" : { phase, widgetId, file }
 *     - "saved"   : { phase, widgetId, file, fileId, url }   // only on instant mode
 *
 * USAGE
 * ─────
 * A) Typical CREATE/UPDATE (recommended): mode='defer'
 *    - File is injected into <input type="file"> and only goes on form submit.
 *
 *    // In your form:
 *    <?= $form->field($model, 'file_id')->fileInput([
 *         'id' => Html::getInputId($model, 'file_id'),
 *         'accept' => 'image/*', 'style' => 'display:none'
 *       ])->label(false); ?>
 *
 *    <?= \croacworks\essentials\widgets\UploadImageInstant::widget([
 *         'mode'           => 'defer',
 *         'hideSaveButton' => true,             // "Crop" injects and closes
 *         'model'          => $model,
 *         'attribute'      => 'file_id',
 *         'fileInputId'    => Html::getInputId($model, 'file_id'),
 *         'imageUrl'       => $model->file->url ?? '',
 *         'aspectRatio'    => '16/9',
 *         'callBackOnSelect' => 'onImageFlow',  // optional global JS callback
 *    ]) ?>
 *
 * B) UPDATE without form submit: mode='instant' (links on upload)
 *
 *    <?= \croacworks\essentials\widgets\UploadImageInstant::widget([
 *         'mode'               => 'instant',
 *         'model'              => $model,           // must have PK
 *         'attribute'          => 'file_id',
 *         'imageUrl'           => $model->file->url ?? '',
 *         'aspectRatio'        => '16/9',
 *         'linkModelOnSend'    => true,            // send model_class/id/field
 *         'deleteOldOnReplace' => true,            // delete old file on server
 *         'authToken'          => Yii::$app->user->identity->access_token ?? null,
 *         'callBackOnSelect'   => 'onImageFlow',   // optional global JS callback
 *    ]) ?>
 *
 * C) Standalone upload (no model) in instant mode
 *
 *    <?= \croacworks\essentials\widgets\UploadImageInstant::widget([
 *         'mode'        => 'instant',
 *         'imageUrl'    => '',
 *         'aspectRatio' => '1',
 *         'callBackOnSelect' => 'onImageFlow', // optional
 *    ]) ?>
 *
 *    <script>
 *      function onImageFlow(payload) {
 *        // payload.phase: "selected" | "cropped" | "saved"
 *        // payload.file: File in all phases; fileId & url only in "saved"
 *        console.log('Image flow:', payload);
 *      }
 *    </script>
 *
 * REMOVAL (handled 100% by Behavior on submit)
 * ────────────────────────────────────────────
 * • The "Remove" button (in both modes) only:
 *     - clears preview and local inputs;
 *     - sets a hidden remove=1 (name controlled by removeFlagParam/Scoped).
 * • The REAL removal (unlink and delete old File) happens on submit,
 *   inside your AttachFileBehavior (deleteOldOnReplace / removal flag).
 *
 * JS EVENTS
 * ─────────
 * • uploadImage:pending — emitted in 'defer' after crop (file is already in the form file input).
 * • uploadImage:saved   — emitted in 'instant' after successful upload:
 *      document.addEventListener('uploadImage:saved', (e) => {
 *        const file = e.detail.file; // { id, url, ... }
 *      });
 *
 * STORAGECONTROLLER — expected contract (/rest/storage/send)
 * ──────────────────────────────────────────────────────────
 * • Required: file (multipart). Use save=1 to receive the File id.
 * • Useful: folder_id, group_id, thumb_aspect, quality.
 * • Direct link to a model:
 *     model_class, model_id, model_field, delete_old(0/1)
 * • Optional pivot:
 *     attach_model (JSON: {class_name, fields:['model_id','file_id'], id:<your model PK>})
 *
 * TIPS / TROUBLESHOOTING
 * ──────────────────────
 * • "Not linked in instant": ensure the record has a PK and linkModelOnSend=true.
 *   Check DevTools to confirm model_class/model_id/model_field are present in POST FormData.
 * • "401/403": check the token (meta, PHP prop, localStorage, cookie). The widget can also use ?access-token=.
 * • "Crop outside viewport": modal is limited to ~92vh; the widget auto-centers the crop box.
 */
class UploadImageInstant extends \yii\bootstrap5\Widget
{
  /** Initial preview URL */
  public string $imageUrl = '';
  public string $accept = 'image/*';

  /** '1', '16/9' or 'NaN' (free) */
  public string $aspectRatio = '1';

  /** Client-side compression */
  public int $maxWidth = 1200;
  public float $maxSizeMB = 3.0;

  /** Send mode: 'defer' (default) or 'instant' */
  public string $mode = 'defer';

  /** Hide "Save" in modal? (in 'defer' the "Crop" already injects and closes) */
  public bool $hideSaveButton = true;

  /** Endpoint (used in 'instant' mode) */
  public $sendUrl = ['/rest/storage/send'];

  /** Model/attribute association */
  public $model = null; // \yii\db\ActiveRecord|null
  public $modelId = null;
  public string $attribute = 'file_id';

  /** Optional: force the file input id of the model */
  public ?string $fileInputId = null;

  /** Optional pivot via attach_model (instant mode) */
  public ?string $attactModelClass = null;
  public array $attactModelFields = [];

  /** Instant upload → include model_* for server-side linking */
  public bool $linkModelOnSend = true;
  public bool $deleteOldOnReplace = true;

  /** Upload params */
  public int $folderId = 2;
  public int $groupId  = 1;
  /** 1 or "W/H" (e.g. "160/99") */
  public $thumbAspect = 1;
  public int $quality  = 85;

  /** Button labels (plain English; JS may translate via yii.t at runtime) */
  public string $labelSelect = 'Select image';
  public string $labelCrop   = 'Crop';
  public string $labelSave   = 'Save';
  public string $labelCancel = 'Cancel';
  public string $labelRemove = 'Remove';
  public string $labelProcessing = 'Processing...';
  public string $labelCropImage  = 'Crop image';

  /** Placeholder image */
  public string $placeholder = '/dummy/code.php?x=250x250/fff/000.jpg&text=NO IMAGE';

  /** Auth (instant mode) */
  public ?string $authToken = null;
  public string $authMetaName = 'api-token';
  public string $authStorageKey = 'token';
  public string $authCookieName = 'token';
  public bool $withCredentials = true;
  public bool $authQueryFallback = true;

  /** Integration with AttachFileBehavior (remove on submit) */
  public string $removeFlagParam  = 'remove';
  public bool   $removeFlagScoped = false; // true => Model[remove]

  /**
   * Global JS function name to call on select/crop/save.
   * Signature: function yourFn({ phase, widgetId, file, fileId, url }) {}
   * - selected: {phase:"selected", file}
   * - cropped : {phase:"cropped", file}
   * - saved   : {phase:"saved", fileId, url, file}
   */
  public ?string $callBackOnSelect = null;

  public function init(): void
  {
    parent::init();
    PluginAsset::register(Yii::$app->view)->add(['cropper']);

    if ($this->model !== null && !method_exists($this->model, 'hasAttribute')) {
      throw new InvalidConfigException('Parameter $model must be an ActiveRecord or null.');
    }
    if (!in_array($this->mode, ['defer', 'instant'], true)) {
      throw new InvalidConfigException('Parameter $mode must be "defer" or "instant".');
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
    $removeHiddenId = "uii_remove_{$id}";

    $initialUrl = $this->imageUrl ?: $this->placeholder;

    // CSRF
    $csrfParam = Yii::$app->request->csrfParam;
    $csrfToken = Yii::$app->request->getCsrfToken();

    // MODEL
    $haveModel  = $this->model !== null && $this->model->hasAttribute($this->attribute);
    $modelClass = $haveModel ? addslashes(get_class($this->model)) : '';
    $modelId    = $haveModel ? $this->modelId  ?? (string)$this->model->getPrimaryKey() : '';

    // URL
    $sendUrl = Url::to($this->sendUrl);

    // Model input name/id
    $inputName   = $haveModel ? Html::getInputName($this->model, $this->attribute) : 'file_id';
    $inputIdPhp  = $this->fileInputId ?: ($haveModel ? Html::getInputId($this->model, $this->attribute) : 'uii_file_' . $id);

    // Remove flag name
    $removeHiddenName = $this->removeFlagScoped && $haveModel
      ? Html::getInputName($this->model, $this->removeFlagParam)
      : $this->removeFlagParam;

    // attach_model
    $attactClass  = $this->attactModelClass ? addslashes($this->attactModelClass) : '';
    $attactFields = $this->attactModelFields;

    // Options
    $aspect  = $this->aspectRatio;
    $maxW    = (int)$this->maxWidth;
    $maxMB   = (float)$this->maxSizeMB;
    $folder  = (int)$this->folderId;
    $group   = (int)$this->groupId;
    $thumb   = is_numeric($this->thumbAspect) ? (int)$this->thumbAspect : "'{$this->thumbAspect}'";
    $quality = (int)$this->quality;

    // Auth
    $authToken         = addslashes((string)($this->authToken ?? ''));
    $authMetaName      = addslashes($this->authMetaName);
    $authStorageKey    = addslashes($this->authStorageKey);
    $authCookieName    = addslashes($this->authCookieName);
    $withCreds         = $this->withCredentials ? 'include' : 'same-origin';
    $authQueryFallback = $this->authQueryFallback ? 'true' : 'false';

    $mode              = $this->mode;
    $hideSaveButton    = $this->hideSaveButton ? 'true' : 'false';

    $linkOnSend        = $this->linkModelOnSend ? 'true' : 'false';
    $deleteOld         = $this->deleteOldOnReplace ? '1' : '0';

    // Callback name (function in window)
    $cbName = $this->callBackOnSelect ? addslashes($this->callBackOnSelect) : '';

    // ===== CSS =====
    $css = <<<CSS
.uploader-card .overlay{
  position:absolute; inset:0; display:none; align-items:center; justify-content:center; z-index:1055;
  background:rgba(0,0,0,.35);
}
.uploader-card .preview{ max-width:600px; }

/* Modal viewport sized */
.uploader-modal .modal-dialog{ max-width:min(95vw, 1200px); margin:1rem auto; }
.uploader-modal .modal-content{ max-height:92vh; }
.uploader-modal .modal-body{ padding:0; overflow:hidden; }

/* Crop area */
.uploader-modal .img-container{
  position:relative;
  width:100%;
  height:calc(92vh - 140px);
  background: conic-gradient(#eee 0 25%, #ddd 0 50%) 0 / 20px 20px;
}
.uploader-modal .img-container img{
  display:block;
  max-width:100%;
}
.btn-group .btn, .btn-group label.btn { padding-top: .5rem; padding-bottom: .5rem; }
CSS;

    // ===== JS =====
    $script = <<<JS
(function(){
  // ---------- i18n helpers (front) ----------
  function tCat(cat, key, fallback){
    try {
      if (window.yii && typeof window.yii.t === 'function') {
        return window.yii.t(cat, key);
      }
    } catch(e){}
    return fallback || key;
  }
  function t(key, fallback){ return tCat('app', key, fallback); }

  // ---------- AUTH ----------
  const AUTH_TOKEN_FROM_PHP = '{$authToken}';
  const AUTH_META_NAME      = '{$authMetaName}';
  const AUTH_STORAGE_KEY    = '{$authStorageKey}';
  const AUTH_COOKIE_NAME    = '{$authCookieName}';
  const WITH_CREDS          = '{$withCreds}';
  const AUTH_QUERY_FALLBACK = {$authQueryFallback};

  function getCookie(name){
    return document.cookie
      .split(';').map(s=>s.trim())
      .find(s=>s.startsWith(name+'='))
      ?.split('=').slice(1).join('=') || '';
  }
  function getAuthToken(){
    const meta  = document.querySelector(`meta[name="\${AUTH_META_NAME}"]`)?.content?.trim();
    const php   = AUTH_TOKEN_FROM_PHP || '';
    const ls    = localStorage.getItem(AUTH_STORAGE_KEY) || '';
    const wnd   = window.AUTH_TOKEN || '';
    const cook  = decodeURIComponent(getCookie(AUTH_COOKIE_NAME) || '');
    return meta || php || ls || wnd || cook || '';
  }
  function commonHeaders() {
    const h = { 'Accept': 'application/json' };
    const tkn = getAuthToken();
    if (tkn) h['Authorization'] = 'Bearer ' + tkn;
    return h;
  }
  function withAccessToken(url){
    if (!AUTH_QUERY_FALLBACK) return url;
    const tkn = getAuthToken();
    if (!tkn) return url;
    const sep = url.includes('?') ? '&' : '?';
    return url + sep + 'access-token=' + encodeURIComponent(tkn);
  }

  // ---------- DOM ----------
  const wrap    = document.getElementById('{$wrapId}');
  const photo   = document.getElementById('{$photoId}');
  const imageEl = document.getElementById('{$imgId}');
  const input   = document.getElementById('{$inputId}');
  const overlay = document.getElementById('{$overlayId}');

  const btnCrop   = document.getElementById('{$cropId}');
  const btnSave   = document.getElementById('{$saveId}');
  btnSave.style.display = 'none';
  const btnCancel = document.getElementById('{$cancelId}');
  const btnRemove = document.getElementById('{$removeBtn}');

  const removeHidden = document.getElementById('{$removeHiddenId}');
  function setRemoveFlag(v){ if (removeHidden) removeHidden.value = String(v); }

  const modalEl = document.getElementById('{$modalId}');
  const modal = new bootstrap.Modal(modalEl, {backdrop:'static', keyboard:false});

  const MODE = '{$mode}';
  const HIDE_SAVE_BTN = {$hideSaveButton};

  const MODEL_CLASS = '{$modelClass}';
  const MODEL_ID    = '{$modelId}';
  const LINK_ON_SEND= {$linkOnSend};
  const DELETE_OLD  = {$deleteOld};

  // Real model file input
  const MODEL_INPUT_ID = '{$inputIdPhp}';
  const MODEL_INPUT_NAME = '{$inputName}';

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

  // In 'instant' sync a hidden [Model][file_id]
  let hidden = null;
  if (MODE === 'instant') {
    hidden = document.querySelector(`input[type="hidden"][name="\${CSS.escape(MODEL_INPUT_NAME)}"]`);
    if (!hidden) {
      const form = wrap.closest('form');
      if (form) {
        hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = MODEL_INPUT_NAME;
        hidden.value = '';
        form.appendChild(hidden);
      }
    }
    // avoid collision with a file input of the same name
    const fileSameName = document.querySelector(`input[type="file"][name="\${CSS.escape(MODEL_INPUT_NAME)}"]`);
    if (fileSameName) fileSameName.name = MODEL_INPUT_NAME + '__ignore';
  }

  // ---------- CONFIG ----------
  const CSRF_PARAM = '{$csrfParam}';
  const CSRF_TOKEN = '{$csrfToken}';

  const MAX_W = {$maxW};
  const MAX_MB = {$maxMB};
  const MAX_BYTES = MAX_MB * 1024 * 1024;
  const ASPECT = (function(){ try { return eval('{$aspect}'); } catch(e){ return NaN; }})();

  const SEND_URL = '{$sendUrl}';

  const FOLDER_ID   = {$folder};
  const GROUP_ID    = {$group};
  const THUMB_ASPECT= {$thumb};
  const QUALITY     = {$quality};

  const attactClass  = '{$attactClass}';
  const attactFields = JSON.parse('{$this->jsonSafe($attactFields)}');

  let tmpFile = null;
  let cropper = null;
  let lastSavedFileId = hidden?.value || null;

  const CB_NAME = '{$cbName}';
  function invokeCallback(payload){
    if (!CB_NAME) return;
    const fn = window[CB_NAME];
    if (typeof fn === 'function') {
      try { fn(payload); } catch(err){ console.error(err); }
    }
  }

  function showOverlay(){ overlay.style.display='flex'; }
  function hideOverlay(){ overlay.style.display='none'; }

  function isImage(file){
    return ["image/jpeg","image/png","image/gif","image/bmp","image/webp"].includes(file.type);
  }

  function errorAlert(msg){
    alert(t(msg, msg));
  }

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
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img,0,0,w,h);
          canvas.toBlob((blob) => {
            if (!blob) return reject(t('Failed to compress image.', 'Failed to compress image.'));
            if (blob.size > MAX_BYTES) return reject(t('Image exceeds the size limit even after compression.', 'Image exceeds the size limit even after compression.'));
            resolve(new File([blob], file.name, {type: file.type, lastModified: Date.now()}));
          }, file.type, 0.85);
        };
        img.onerror = () => reject(t('Failed to load image.', 'Failed to load image.'));
        img.src = e.target.result;
      };
      reader.onerror = () => reject(t('Failed to read the file.', 'Failed to read the file.'));
      reader.readAsDataURL(file);
    });
  }

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
    cropper.setCropBoxData({
      width:  w,
      height: h,
      left:   (cont.width  - w) / 2,
      top:    (cont.height - h) / 2
    });
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

    if (LINK_ON_SEND && MODEL_CLASS && MODEL_ID) {
      fd.append('model_class', MODEL_CLASS);
      fd.append('model_id', String(MODEL_ID));
      fd.append('model_field', MODEL_INPUT_NAME.split(']').slice(-2, -1)[0] || 'file_id');
      fd.append('delete_old', String(DELETE_OLD));
    }

    if (attactClass && attactFields.length === 2 && MODEL_ID) {
      const payload = { class_name: attactClass, fields: attactFields, id: MODEL_ID };
      fd.append('attach_model', JSON.stringify(payload));
    }

    const urlSend = withAccessToken('{$sendUrl}');
    const res = await fetch(urlSend, {
      method: 'POST',
      body: fd,
      headers: commonHeaders(),
      credentials: WITH_CREDS,
    });
    if(!res.ok) throw new Error(t('Upload failed ({0}).', 'Upload failed ({0}).').replace('{0}', String(res.status)));
    const json = await res.json();
    if (!json || json.success !== true) {
      const msg = (json && json.data) ? JSON.stringify(json.data) : t('Invalid server response.', 'Invalid server response.');
      throw new Error(t('Upload not accepted: {0}', 'Upload not accepted: {0}').replace('{0}', msg));
    }
    return json.data; // File model
  }

  // -------- i18n: ensure modal texts translated when shown --------
  modalEl.addEventListener('show.bs.modal', () => {
    const titleEl = modalEl.querySelector('.modal-title');
    if (titleEl) titleEl.textContent = t('Crop image', 'Crop image');
    const btnCrop = modalEl.querySelector('#{$cropId}');
    if (btnCrop) btnCrop.lastChild.nodeValue = ' ' + t('Crop', 'Crop');
    const btnCancel = modalEl.querySelector('#{$cancelId}');
    if (btnCancel) btnCancel.textContent = t('Cancel', 'Cancel');
  });

  // -------- Events --------
  input.addEventListener('change', async (e) => {
    const files = e.target.files;
    if(!files || !files.length) return;
    tmpFile = files[0];
    if (!isImage(tmpFile)) { errorAlert('Invalid file.'); return; }

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
      // callback: selected
      invokeCallback({ phase: 'selected', widgetId: '{$id}', file: tmpFile });
      modal.show();
    } catch(err){
      errorAlert(err.message || String(err));
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

  // CROP: in 'defer' injects file and closes; in 'instant' only updates preview
  btnCrop.addEventListener('click', async () => {
    if (!cropper) return;
    try{
      showOverlay();
      const canvas = cropper.getCroppedCanvas();
      const blob = await new Promise(res => canvas.toBlob(res, tmpFile?.type || 'image/jpeg', 0.9));
      if (!blob) throw new Error(t('Failed to generate crop.', 'Failed to generate crop.'));

      let finalFile = (tmpFile?.type === 'image/png')
        ? new File([blob], tmpFile.name, {type: blob.type})
        : await (async () => {
            const f = new File([blob], tmpFile?.name || 'image.jpg', {type: blob.type});
            return await compressImage(f);
          })();

      // preview always
      photo.src = URL.createObjectURL(finalFile);

      if (MODE === 'defer') {
        assignFileToModelInput(finalFile);
        setRemoveFlag(0);
        // callback: cropped (defer)
        invokeCallback({ phase: 'cropped', widgetId: '{$id}', file: finalFile });
        document.dispatchEvent(new CustomEvent('uploadImage:pending', { detail: { widgetId: '{$id}' }}));
      }
      btnSave.style.display = 'block';
      modal.hide();
    } catch (err){
      errorAlert(err.message || String(err));
    } finally {
      hideOverlay();
    }
  });

  // SAVE: in 'defer' same as CROP; in 'instant' uploads to server
  btnSave.addEventListener('click', async () => {
    if (!cropper) return;
    try{
      showOverlay();
      const canvas = cropper.getCroppedCanvas();
      const blob = await new Promise(res => canvas.toBlob(res, tmpFile?.type || 'image/jpeg', 0.9));
      if(!blob) throw new Error(t('Failed to generate crop.', 'Failed to generate crop.'));

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
        // callback: cropped (defer save path)
        invokeCallback({ phase: 'cropped', widgetId: '{$id}', file: finalFile });
        modal.hide();
        return;
      }

      // instant
      const saved = await uploadFinalFile(finalFile);
      lastSavedFileId = saved.id || null;
      if (saved.url) photo.src = saved.url + '?v=' + Date.now();
      if (hidden) hidden.value = String(lastSavedFileId ?? '');
      setRemoveFlag(0);
      document.dispatchEvent(new CustomEvent('uploadImage:saved', { detail: { file: saved, widgetId: '{$id}' }}));
      // callback: saved (instant)
      invokeCallback({ phase: 'saved', widgetId: '{$id}', file: finalFile, fileId: saved.id || null, url: saved.url || '' });
      modal.hide();
    } catch(err){
      console.error(err);
      errorAlert(err.message || String(err));
    } finally {
      hideOverlay();
    }
  });

  // REMOVE — UI only; real removal handled by the Behavior on submit
  btnRemove.addEventListener('click', async () => {
    try{
      showOverlay();
      photo.src = '{$this->escapeJs($this->placeholder)}';
      btnSave.style.display = 'none';

      if (modelFileInput) modelFileInput.value = '';
      if (hidden) hidden.value = '';

      setRemoveFlag(1);
    } catch(err){
      console.error(err);
    } finally {
      hideOverlay();
    }
  });

  // Translate static pieces already visible on load (progress text, buttons)
  (function translateStatic(){
    const prog = document.querySelector('#{$overlayId} strong');
    if (prog) prog.textContent = t('Processing...', 'Processing...');
    const lblSelect = document.querySelector('label[for="{$inputId}"]');
    if (lblSelect) {
      // keep icon, translate text only (after the icon)
      const icon = lblSelect.querySelector('i');
      lblSelect.textContent = '';
      if (icon) lblSelect.appendChild(icon);
      lblSelect.appendChild(document.createTextNode(' ' + t('Select image', 'Select image')));
    }
    const bSave = document.getElementById('{$saveId}');
    if (bSave) {
      const icon = bSave.querySelector('i');
      bSave.textContent = '';
      if (icon) bSave.appendChild(icon);
      bSave.appendChild(document.createTextNode(' ' + t('Save', 'Save')));
    }
    const bRemove = document.getElementById('{$removeBtn}');
    if (bRemove) {
      const icon = bRemove.querySelector('i');
      bRemove.textContent = '';
      if (icon) bRemove.appendChild(icon);
      bRemove.appendChild(document.createTextNode(' ' + t('Remove', 'Remove')));
    }
  })();

})();
JS;

    $view->registerCss($css);
    $view->registerJs($script, \yii\web\View::POS_END);

    $showRemove = ($this->imageUrl !== '') ? '' : 'd-none';

    ob_start(); 
  ?>
    <div id="<?= $wrapId ?>">
      <div class="card uploader-card">
        <div class="card-body position-relative">

          <div id="<?= $overlayId ?>" class="overlay">
            <div class="text-white d-flex align-items-center gap-2">
              <strong><?= Html::encode($this->labelProcessing) ?></strong>
              <div class="spinner-border ms-2" role="status" aria-hidden="true"></div>
            </div>
          </div>

          <div class="text-center pb-1">
            <img id="<?= $photoId ?>" class="rounded preview" src="<?= Html::encode($initialUrl) ?>" alt="preview">
          </div>

          <div class="text-center pb-2">
            <!-- hidden file chooser that opens via label -->
            <input id="<?= $inputId ?>" type="file" accept="<?= Html::encode($this->accept) ?>" class="d-none">

            <!-- removal flag for Behavior -->
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
              <h5 id="<?= $modalId ?>_label" class="modal-title"><?= Html::encode($this->labelCropImage) ?></h5>
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

  private function escapeJs(string $s): string
  {
    return addslashes($s);
  }
}
