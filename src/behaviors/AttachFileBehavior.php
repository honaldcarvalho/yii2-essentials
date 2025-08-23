<?php

namespace croacworks\essentials\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use croacworks\essentials\controllers\rest\StorageController;

/**
 * AttachFileBehavior
 *
 * - Faz upload automático para o atributo (ex.: file_id) no BEFORE_VALIDATE.
 * - No UPDATE, se houver novo upload, apaga o arquivo anterior no AFTER_SAVE.
 * - Opcionalmente, permite remoção explícita via flag (ex.: 'remove_file_id').
 *
 * Exemplo de uso no model:
 *  public function behaviors()
 *  {
 *      return [
 *          [
 *              'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
 *              'attribute' => 'file_id',
 *              'removeFlagParam' => 'remove_file_id', // opcional
 *              'deleteOldOnReplace' => true,
 *          ],
 *      ];
 *  }
 */
class AttachFileBehavior extends Behavior
{
    /** @var string Atributo do model que guarda o ID do arquivo (ex.: file_id) */
    public string $attribute = 'file_id';

    /** @var string|null Nome do parâmetro POST que indica remoção explícita do arquivo */
    public ?string $removeFlagParam = null;

    /** @var bool Se true, apaga o arquivo antigo quando for substituído por um novo */
    public bool $deleteOldOnReplace = true;

    /** @var int|string|null Guarda o ID antigo para exclusão após salvar */
    private $oldFileId = null;

    /** @var bool Indica se o usuário pediu remoção explícita (sem novo upload) */
    private bool $explicitRemoveRequested = false;

    /** @inheritDoc */
    public function events(): array
    {
        return [
            \yii\base\Model::EVENT_BEFORE_VALIDATE   => 'beforeValidateHandle',
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSaveHandle',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSaveHandle',
        ];
    }
    /**
     * Captura o arquivo, faz upload e marca o arquivo antigo para exclusão pós-save.
     * Também processa o flag de remoção explícita (sem novo upload).
     */
    public function beforeValidateHandle(): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $attr  = $this->attribute;

        // Guarda o file_id antigo para possível exclusão após salvar
        $this->oldFileId = $owner->getOldAttribute($attr);

        // 1) Remoção explícita via flag (sem novo upload)
        $this->explicitRemoveRequested = false;
        if ($this->removeFlagParam) {
            $flag = Yii::$app->request->post($this->removeFlagParam);
            // Considera "1", "true", "on" como verdadeiros
            if ($flag !== null && in_array(strtolower((string)$flag), ['1', 'true', 'on'], true)) {
                $owner->$attr = null; // zera o atributo; exclusão real ocorrerá no afterSave
                $this->explicitRemoveRequested = true;
            }
        }

        // 2) Upload de novo arquivo (substituição)
        $uploaded = UploadedFile::getInstance($owner, $attr);
        if ($uploaded instanceof UploadedFile) {
            $result = StorageController::uploadFile($uploaded, ['save' => true]);

            if (is_array($result) && ($result['success'] ?? false) === true && isset($result['data']['id'])) {
                // Atribui o novo ID ao atributo
                $owner->$attr = $result['data']['id'];
            } else {
                $owner->addError($attr, Yii::t('app', 'Falha ao enviar o arquivo.'));
            }
        }
    }

    /**
     * Após salvar com sucesso:
     * - Se houve substituição (file_id mudou) e habilitado, apaga o antigo.
     * - Se houve remoção explícita (sem novo upload), apaga o antigo.
     */
    public function afterSaveHandle(): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $attr  = $this->attribute;

        $currentId = $owner->$attr;
        $oldId     = $this->oldFileId;

        // 1) Substituição por novo arquivo
        if ($this->deleteOldOnReplace && $oldId && $currentId && (string)$oldId !== (string)$currentId) {
            $this->deleteFileById($oldId);
        }

        // 2) Remoção explícita (sem novo upload)
        if ($this->explicitRemoveRequested && $oldId && !$currentId) {
            $this->deleteFileById($oldId);
        }

        // Limpa estado interno
        $this->oldFileId = null;
        $this->explicitRemoveRequested = false;
    }

    /**
     * Apaga o arquivo no storage pelo ID.
     * Ajuste este método caso a sua API de Storage use outro nome.
     */
    protected function deleteFileById($fileId): void
    {
        try {
            // Considerando endpoints do StorageController (/storage/delete)
            // Ajuste o método conforme a sua implementação:
            // ex.: StorageController::delete($fileId);
            if (method_exists(StorageController::class, 'removeFileY')) {
                StorageController::removeFile($fileId);
            } else {
                // Como fallback, tente um service registrado na app, se existir:
                if (Yii::$app->has('storageService')) {
                    /** @var object $svc */
                    $svc = Yii::$app->get('storageService');
                    if (method_exists($svc, 'deleteById')) {
                        $svc->deleteById($fileId);
                    }
                }
            }
        } catch (\Throwable $e) {
            Yii::error("Erro ao deletar arquivo #{$fileId}: " . $e->getMessage(), __METHOD__);
            // Não lança exceção para não quebrar o fluxo do save
        }
    }
}
