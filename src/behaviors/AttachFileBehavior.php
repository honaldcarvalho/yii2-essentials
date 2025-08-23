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
 * Automatically handles file upload for a model attribute (e.g., file_id).
 * 
 * Usage:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
 *             'attribute' => 'file_id', // the field in DB
 *         ],
 *     ];
 * }
 * ```
 */
class AttachFileBehavior extends Behavior
{
    /**
     * @var string the model attribute that stores file_id
     */
    public string $attribute = 'file_id';

    /**
     * @var UploadedFile|null
     */
    protected ?UploadedFile $uploadedFile = null;

    /**
     * Events
     */

    private $oldFileId = null;

    public function events(): array
    {
        return [
            \yii\base\Model::EVENT_BEFORE_VALIDATE   => 'handleUpload',
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSaveHandle',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSaveHandle',
        ];
    }

    /**
     * Handles file upload before validation
     */
    public function handleUpload(): void
    {
        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        $attr  = $this->attribute;

        // guarda o antigo de forma confiável
        $this->oldFileId = $owner->getOldAttribute($attr);

        $file = \yii\web\UploadedFile::getInstance($owner, $attr);
        if (!$file instanceof \yii\web\UploadedFile) {
            return;
        }

        $result = \croacworks\essentials\controllers\StorageController::uploadFile($file, ['save' => true]);

        if (is_array($result) && ($result['success'] ?? false) === true && isset($result['data']['id'])) {
            // seta o novo ID no atributo (ainda não apaga o antigo!)
            $owner->$attr = $result['data']['id'];
        } else {
            $owner->addError($attr, Yii::t('app', 'Failed to upload file.'));
        }
    }

    public function afterSaveHandle(): void
    {
        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        $attr  = $this->attribute;

        $newId = $owner->$attr;
        $oldId = $this->oldFileId;

        // só apaga se trocou de fato e houve save com sucesso
        if ($oldId && $newId && (string)$oldId !== (string)$newId) {
            StorageController::removeFile($oldId);
        }

        $this->oldFileId = null;
    }
}
