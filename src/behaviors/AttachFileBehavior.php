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
 * Keeps and replaces related file (e.g. field `file_id`) safely.
 */
class AttachFileBehavior extends Behavior
{
    public string $attribute = 'file_id';
    public string $removeFlagParam = 'remove';
    public bool $deleteOldOnReplace = true;
    public bool $deleteOnOwnerDelete = false;
    public bool $debug = false;
    public bool $emptyMeansRemove = false;

    private bool $alreadyUploaded = false;
    private $oldId;
    private $toDeleteId = null;

    public function events(): array
    {
        return [
            Model::EVENT_BEFORE_VALIDATE => 'handleUploadOrKeep',
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

    private function buildUploadErrorMessage(array $resp): string
    {
        $type    = $resp['error']['type']    ?? 'error.unknown';
        $message = $resp['error']['message'] ?? 'Upload failed.';
        $ctx     = $resp['error']['context'] ?? [];
        $file    = $resp['error']['file']    ?? null;
        $line    = $resp['error']['line']    ?? null;

        // Fallback: extrai file:line da 1ª linha do trace, se existir
        if ((!$file || !$line) && !empty($resp['error']['trace'])) {
            $trace = (string)$resp['error']['trace'];
            // Padrões comuns: "#0 /caminho/arquivo.php(123): ..."
            if (preg_match('/#0\s+([^\(]+)\((\d+)\):/', $trace, $m)) {
                $file = $file ?: ($m[1] ?? null);
                $line = $line ?: (isset($m[2]) ? (int)$m[2] : null);
            }
        }

        $parts = [];
        $parts[] = $message;

        if ($file && $line) {
            $parts[] = "at {$file}:{$line}";
        }

        if (!empty($ctx)) {
            $parts[] = 'Context: ' . json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return implode(' | ', $parts);
    }


    public function handleUploadOrKeep(ModelEvent $event): void
    {
        if ($this->alreadyUploaded) {
            return;
        }
        $this->alreadyUploaded = true;
        
        $owner = $this->owner;
        $attr  = $this->attribute;
        $req   = Yii::$app->request;

        $postedModel  = $req->post($owner->formName(), []);
        $hasPostedKey = array_key_exists($attr, $postedModel) || $req->post($attr, null) !== null;
        $postedId     = $hasPostedKey ? ($postedModel[$attr] ?? $req->post($attr, null)) : null;

        $removeFlag = (int)($req->post($this->removeFlagParam, $postedModel[$this->removeFlagParam] ?? 0));

        $uploaded = UploadedFile::getInstance($owner, $attr);
        if ($uploaded instanceof UploadedFile) {
            if ($uploaded->error !== UPLOAD_ERR_OK) {
                $msg = $this->phpUploadErrorName((int)$uploaded->error);
                $owner->addError($attr, "Upload failed: {$msg}");
                $event->isValid = false;
                return;
            }

            try {
                $resp = StorageController::uploadFile($uploaded, ['save' => true, 'thumb_aspect' => 1]);

                if (!empty($resp['success'])) {
                    $this->alreadyUploaded = true;
                    $newId = 0;
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

                    $owner->addError($attr, 'Upload succeeded but file ID was not returned.');
                    $event->isValid = false;
                    return;
                }

                $owner->addError($attr, $this->buildUploadErrorMessage($resp));
                $event->isValid = false;
                return;
            } catch (\Throwable $e) {
                $owner->addError($attr, "Upload failed: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}");
                $event->isValid = false;
                return;
            }
        }

        if ($removeFlag === 1) {
            if ($this->oldId) {
                $this->toDeleteId = $this->oldId;
            }
            $owner->{$attr} = null;
            return;
        }

        if ($hasPostedKey) {
            $raw = trim((string)$postedId);
            if ($raw === '') {
                $owner->{$attr} = $this->oldId;
                return;
            }
            if ($raw === '0' || strtolower($raw) === 'null') {
                if ($this->emptyMeansRemove) {
                    if ($this->oldId) $this->toDeleteId = $this->oldId;
                    $owner->{$attr} = null;
                } else {
                    $owner->{$attr} = $this->oldId;
                }
                return;
            }
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

        $owner->{$attr} = $this->oldId;
    }

    public function deleteOldIfNeeded(AfterSaveEvent $event): void
    {
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
