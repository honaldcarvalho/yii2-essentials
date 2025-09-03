<?php

namespace croacworks\essentials\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\db\AfterSaveEvent;
use yii\web\UploadedFile;
use croacworks\essentials\controllers\rest\StorageController;

/**
 * AttachFileBehavior
 * ------------------
 * Mantém e troca o arquivo relacionado (ex.: campo `file_id`) sem “apagar sem querer”.
 *
 * Regras:
 * 1) Upload síncrono (modo defer — <input type="file" name="Model[file_id]">):
 *    - Se vier arquivo, faz o upload via StorageController::uploadFile(save=1) e seta o novo id.
 *    - Se `deleteOldOnReplace=true`, remove o arquivo antigo no AFTER_SAVE.
 *
 * 2) ID vindo por hidden (modo instant ou outro fluxo):
 *    - Se for inteiro válido diferente do antigo, troca e marca o antigo para remoção.
 *    - Se vier string vazia '', **NÃO remove**: apenas mantém o antigo.
 *    - Se vier '0' ou 'null', **só remove** se `removeFlagParam`=1 (ou se `emptyMeansRemove=true`).
 *
 * 3) Flag de remoção isolada (`removeFlagParam`=1) sem ID: zera o atributo e marca o antigo para remoção.
 *
 * 4) Caso nada tenha mudado, mantém o valor antigo.
 *
 * Dicas:
 * - Garanta que o form NÃO tenha um hidden `Model[file_id]` vazio por padrão.
 * - O widget deve mandar um hidden `remove=1` apenas quando o usuário clicar em “Remover”.
 */
class AttachFileBehavior extends Behavior
{
    /** atributo que guarda o id do File (ex.: file_id) */
    public string $attribute = 'file_id';

    /** nome da flag de remoção no POST (pode ser global ou aninhado em Model[remove]) */
    public string $removeFlagParam = 'remove';

    /** apaga o arquivo antigo ao trocar */
    public bool $deleteOldOnReplace = true;

    /** apaga o arquivo ao deletar o dono */
    public bool $deleteOnOwnerDelete = false;

    /** ligar logs (Yii::info) */
    public bool $debug = false;

    /** por padrão, vazio NÃO remove; se true, '' passa a significar remover (não recomendado) */
    public bool $emptyMeansRemove = false;

    /**
     * Flag interna para não processar duas vezes
     */
    private bool $alreadyUploaded = false;

    private $oldId;
    private $toDeleteId = null;

    public function events(): array
    {
        return [
            Model::EVENT_BEFORE_VALIDATE          => 'rememberOld',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'handleUploadOrKeep',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'handleUploadOrKeep',
            BaseActiveRecord::EVENT_AFTER_INSERT  => 'deleteOldIfNeeded',
            BaseActiveRecord::EVENT_AFTER_UPDATE  => 'deleteOldIfNeeded',
            BaseActiveRecord::EVENT_AFTER_DELETE  => 'deleteOnDelete',
        ];
    }

    private function log($msg, $data = []): void
    {
        if ($this->debug) {
            Yii::info(['attachFile' => $msg, 'data' => $data], 'attach.file');
        }
    }

    public function rememberOld(ModelEvent $event): void
    {
        $attr = $this->attribute;
        $this->oldId = $this->owner->getOldAttribute($attr) ?? $this->owner->{$attr};
        $this->log('rememberOld', ['oldId' => $this->oldId]);
    }

    /**
     * Traduz código de erro do PHP upload.
     */
    private function phpUploadErrorName(int $code): string
    {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'UPLOAD_ERR_INI_SIZE (File exceeds upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'UPLOAD_ERR_FORM_SIZE (File exceeds the MAX_FILE_SIZE form limit).',
            UPLOAD_ERR_PARTIAL    => 'UPLOAD_ERR_PARTIAL (The uploaded file was only partially uploaded).',
            UPLOAD_ERR_NO_FILE    => 'UPLOAD_ERR_NO_FILE (No file was uploaded).',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR (Missing a temporary folder).',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE (Failed to write file to disk).',
            UPLOAD_ERR_EXTENSION  => 'UPLOAD_ERR_EXTENSION (A PHP extension stopped the file upload).',
            0                     => 'OK',
        ];
        return $map[$code] ?? "UNKNOWN ({$code})";
    }

    /**
     * Constrói uma mensagem amigável a partir da resposta padronizada do StorageController.
     * Mantém mensagens em inglês e injeta contexto útil.
     */
    private function buildUploadErrorMessage(array $resp): string
    {
        // Formato esperado:
        // ['code'=>int,'success'=>false,'error'=>['type'=>string,'message'=>string,'context'=>array]]
        $type    = $resp['error']['type']    ?? 'error.unknown';
        $message = $resp['error']['message'] ?? 'Upload failed.';
        $ctx     = $resp['error']['context'] ?? [];

        $parts = [];
        $parts[] = Yii::t('app', '{message}', ['message' => $message]);

        // Detalhes úteis por tipo
        if ($type === 'filesystem.write_failed') {
            $target       = $ctx['target']       ?? null;
            $dir          = $ctx['dir']          ?? null;
            $dirExists    = isset($ctx['dir_exists']) ? ($ctx['dir_exists'] ? 'yes' : 'no') : null;
            $dirWritable  = isset($ctx['dir_writable']) ? ($ctx['dir_writable'] ? 'yes' : 'no') : null;
            $freeSpace    = $ctx['free_space']   ?? null;
            $phpErr       = $ctx['file_error_name'] ?? null;

            if ($target)      $parts[] = Yii::t('app', 'Target: {p}', ['p' => $target]);
            if ($dir)         $parts[] = Yii::t('app', 'Directory: {p}', ['p' => $dir]);
            if ($dirExists)   $parts[] = Yii::t('app', 'Directory exists: {v}', ['v' => $dirExists]);
            if ($dirWritable) $parts[] = Yii::t('app', 'Directory writable: {v}', ['v' => $dirWritable]);
            if ($freeSpace)   $parts[] = Yii::t('app', 'Free space: {v} bytes', ['v' => (string)$freeSpace]);
            if ($phpErr)      $parts[] = Yii::t('app', 'PHP upload status: {s}', ['s' => $phpErr]);
        }

        if ($type === 'db.validation_failed') {
            // Mostrar primeiro erro de validação
            $first = $ctx['firstErrors'] ?? [];
            if (!empty($first)) {
                foreach ($first as $field => $err) {
                    // err pode ser string
                    $parts[] = Yii::t('app', 'Validation: {field} - {error}', ['field' => $field, 'error' => (string)$err]);
                    break; // apenas o primeiro
                }
            }
        }

        if ($type === 'upload.php_error') {
            $phpName = $ctx['php_error_name'] ?? null;
            if ($phpName) {
                $parts[] = Yii::t('app', 'PHP upload error: {s}', ['s' => $phpName]);
            }
        }

        if ($type === 'image.process_failed' || $type === 'video.encode_failed') {
            $detail = $ctx['detail'] ?? null;
            $stage  = $ctx['stage']  ?? null;
            if ($stage)  $parts[] = Yii::t('app', 'Stage: {s}', ['s' => $stage]);
            if ($detail) $parts[] = Yii::t('app', 'Detail: {d}', ['d' => $detail]);
        }

        // Fallback: se sobrou algum contexto útil
        if (empty($ctx) === false && in_array($type, ['filesystem.write_failed','db.validation_failed','upload.php_error','image.process_failed','video.encode_failed']) === false) {
            $parts[] = Yii::t('app', 'Context: {c}', ['c' => json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }

        return implode(' | ', $parts);
    }

    public function handleUploadOrKeep(ModelEvent $event): void
    {
        if ($this->alreadyUploaded) {
            return;
        }

        $owner = $this->owner;
        $attr  = $this->attribute;
        $req   = Yii::$app->request;

        // POST aninhado/flat
        $postedModel  = $req->post($owner->formName(), []);
        $hasPostedKey = array_key_exists($attr, $postedModel) || $req->post($attr, null) !== null;
        $postedId     = $hasPostedKey ? ($postedModel[$attr] ?? $req->post($attr, null)) : null;

        // Flag de remoção (global ou aninhada)
        $removeFlag = (int)($req->post($this->removeFlagParam, $postedModel[$this->removeFlagParam] ?? 0));

        // 1) Upload síncrono
        $uploaded = UploadedFile::getInstance($owner, $attr);
        if ($uploaded instanceof UploadedFile) {
            // Antes de enviar, trate possíveis códigos de erro do PHP upload
            if ($uploaded->error !== UPLOAD_ERR_OK) {
                $msg = $this->phpUploadErrorName((int)$uploaded->error);
                $owner->addError($attr, Yii::t('app', 'Upload failed: {msg}', ['msg' => $msg]));
                $event->isValid = false;
                return;
            }

            try {
                $resp = StorageController::uploadFile($uploaded, ['save' => true, 'thumb_aspect' => 1]);

                if (!empty($resp['success'])) {
                    $this->alreadyUploaded = true;
                    $newId = 0;

                    // uploadFile() pode retornar data como objeto (ActiveRecord) ou array
                    if (is_object($resp['data']) && isset($resp['data']->id)) {
                        $newId = (int)$resp['data']->id;
                    } elseif (is_array($resp['data']) && isset($resp['data']['id'])) {
                        $newId = (int)$resp['data']['id'];
                    }

                    if ($newId > 0) {
                        $owner->{$attr} = $newId;
                        if ($this->deleteOldOnReplace && $this->oldId && $this->oldId != $newId) {
                            $this->toDeleteId = $this->oldId;
                        }
                        return;
                    }

                    // Upload disse sucesso, mas não retornou id
                    $owner->addError($attr, Yii::t('app', 'Upload succeeded but file ID was not returned.'));
                    $event->isValid = false;
                    return;
                }

                // Falhou — use mensagem rica do StorageController
                $owner->addError($attr, $this->buildUploadErrorMessage($resp));
                $event->isValid = false;
                return;

            } catch (\Throwable $e) {
                // Exceções não mapeadas — mensagem genérica (em inglês)
                $owner->addError($attr, Yii::t('app', 'Upload failed due to an unexpected error.'));
                $this->log('upload exception', ['err' => $e->getMessage()]);
                $event->isValid = false;
                return;
            }
        }

        // 2) ***PRIORIDADE PARA REMOÇÃO EXPLÍCITA***
        // Se usuário clicou "Remover", removemos independentemente do postedId estar vazio
        if ($removeFlag === 1) {
            if ($this->oldId) {
                $this->toDeleteId = $this->oldId;
            }
            $owner->{$attr} = null;
            return;
        }

        // 3) ID vindo por hidden (instant/defer)
        if ($hasPostedKey) {
            $raw = trim((string)$postedId);

            // vazio SEM flag de remoção -> mantém
            if ($raw === '') {
                $owner->{$attr} = $this->oldId;
                return;
            }

            // '0'/'null' só remove se explicitado via emptyMeansRemove (opcional)
            if ($raw === '0' || strtolower($raw) === 'null') {
                if ($this->emptyMeansRemove) {
                    if ($this->oldId) $this->toDeleteId = $this->oldId;
                    $owner->{$attr} = null;
                } else {
                    $owner->{$attr} = $this->oldId;
                }
                return;
            }

            // novo id válido
            $newId = (int)$raw;
            if ($newId !== (int)$this->oldId) {
                if ($this->deleteOldOnReplace && $this->oldId) {
                    $this->toDeleteId = $this->oldId;
                }
                $owner->{$attr} = $newId;
            } else {
                $owner->{$attr} = $this->oldId;
            }
            return;
        }

        // 4) Nada mudou → mantém
        $owner->{$attr} = $this->oldId;
    }

    public function deleteOldIfNeeded(AfterSaveEvent $event): void
    {
        // Segurança: não remover o id atual por engano
        $currentId = (int)$this->owner->{$this->attribute};
        if ($this->toDeleteId && (int)$this->toDeleteId !== $currentId) {
            $this->log('delete old', ['id' => $this->toDeleteId]);
            try {
                StorageController::removeFile($this->toDeleteId);
            } catch (\Throwable $e) {
                $this->log('delete old exception', ['err' => $e->getMessage()]);
            }
        }
        $this->toDeleteId = null;
    }

    public function deleteOnDelete(Event $event): void
    {
        if ($this->deleteOnOwnerDelete) {
            $id = (int)$this->owner->{$this->attribute};
            if ($id) {
                $this->log('delete on owner delete', ['id' => $id]);
                try {
                    StorageController::removeFile($id);
                } catch (\Throwable $e) {
                    $this->log('delete on owner delete exception', ['err' => $e->getMessage()]);
                }
            }
        }
    }
}
